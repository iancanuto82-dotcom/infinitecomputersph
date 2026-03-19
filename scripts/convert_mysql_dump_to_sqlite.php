<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php scripts/convert_mysql_dump_to_sqlite.php <input.sql> <output.sql> <output.sqlite>\n");
    exit(1);
}

$inputPath = $argv[1];
$outputSqlPath = $argv[2];
$outputDbPath = $argv[3];

if (!is_file($inputPath)) {
    fwrite(STDERR, "Input file not found: {$inputPath}\n");
    exit(1);
}

$rawSql = file_get_contents($inputPath);
if ($rawSql === false) {
    fwrite(STDERR, "Failed to read input file: {$inputPath}\n");
    exit(1);
}

$rawSql = normalizeNewlines($rawSql);
$cleanSql = stripSqlComments($rawSql);
$statements = splitSqlStatements($cleanSql);

$tables = [];
$tableOrder = [];
$metadata = [];
$insertStatements = [];

foreach ($statements as $statement) {
    if (preg_match('/^CREATE TABLE\s+`([^`]+)`\s*\((.*)\)\s*ENGINE=.*$/si', $statement, $matches)) {
        $tableName = $matches[1];
        $tables[$tableName] = parseCreateTableBody($matches[2]);
        $tableOrder[] = $tableName;
        $metadata[$tableName] ??= defaultTableMetadata();
        continue;
    }

    if (preg_match('/^ALTER TABLE\s+`([^`]+)`\s+(.*)$/si', $statement, $matches)) {
        $tableName = $matches[1];
        $metadata[$tableName] ??= defaultTableMetadata();
        mergeTableMetadata($metadata[$tableName], $matches[2]);
        continue;
    }

    if (preg_match('/^INSERT INTO\s+`([^`]+)`/i', $statement)) {
        $insertStatements[] = convertMySqlInsertToSqlite($statement) . ';';
    }
}

$sqliteLines = [];
$sqliteLines[] = 'PRAGMA foreign_keys = OFF;';

foreach ($tableOrder as $tableName) {
    $sqliteLines[] = buildCreateTableSql($tableName, $tables[$tableName], $metadata[$tableName]) . ';';
}

foreach ($insertStatements as $insertStatement) {
    $sqliteLines[] = $insertStatement;
}

foreach ($tableOrder as $tableName) {
    foreach (buildIndexSql($tableName, $metadata[$tableName]) as $indexSql) {
        $sqliteLines[] = $indexSql . ';';
    }
}

$sqliteLines[] = 'PRAGMA foreign_keys = ON;';
$sqliteSql = implode("\n\n", $sqliteLines) . "\n";

if (file_put_contents($outputSqlPath, $sqliteSql) === false) {
    fwrite(STDERR, "Failed to write converted SQL file: {$outputSqlPath}\n");
    exit(1);
}

if (is_file($outputDbPath) && !unlink($outputDbPath)) {
    fwrite(STDERR, "Failed to remove existing SQLite database: {$outputDbPath}\n");
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $outputDbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = OFF;');

    foreach (splitSqlStatements($sqliteSql) as $statementIndex => $sqliteStatement) {
        try {
            $pdo->exec($sqliteStatement);
        } catch (Throwable $throwable) {
            fwrite(STDERR, "Failed statement #{$statementIndex}:\n");
            fwrite(STDERR, substr($sqliteStatement, 0, 1200) . "\n\n");
            throw $throwable;
        }
    }

    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (Throwable $throwable) {
    fwrite(STDERR, "SQLite import failed: {$throwable->getMessage()}\n");
    exit(1);
}

$summaryTables = ['audit_logs', 'products', 'sales', 'sale_items', 'users', 'categories'];
$summary = [];

foreach ($summaryTables as $tableName) {
    if (!isset($tables[$tableName])) {
        continue;
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . quoteIdentifier($tableName))->fetchColumn();
    $summary[] = "{$tableName}={$count}";
}

echo "Converted SQL: {$outputSqlPath}\n";
echo "SQLite DB: {$outputDbPath}\n";
echo 'Row counts: ' . implode(', ', $summary) . "\n";

function normalizeNewlines(string $value): string
{
    return str_replace(["\r\n", "\r"], "\n", $value);
}

function stripSqlComments(string $sql): string
{
    $sql = preg_replace('/\/\*![\s\S]*?\*\//', '', $sql) ?? $sql;
    $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    return trim($sql);
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $buffer .= $char;

        if ($inSingleQuote) {
            if ($char === '\\') {
                if ($index + 1 < $length) {
                    $buffer .= $sql[++$index];
                }
                continue;
            }

            if ($char === "'") {
                if ($index + 1 < $length && $sql[$index + 1] === "'") {
                    $buffer .= $sql[++$index];
                    continue;
                }

                $inSingleQuote = false;
            }

            continue;
        }

        if ($inDoubleQuote) {
            if ($char === '"') {
                $inDoubleQuote = false;
            }

            continue;
        }

        if ($inBacktick) {
            if ($char === '`') {
                $inBacktick = false;
            }

            continue;
        }

        if ($char === "'") {
            $inSingleQuote = true;
            continue;
        }

        if ($char === '"') {
            $inDoubleQuote = true;
            continue;
        }

        if ($char === '`') {
            $inBacktick = true;
            continue;
        }

        if ($char === ';') {
            $statement = trim(substr($buffer, 0, -1));
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function defaultTableMetadata(): array
{
    return [
        'primary' => [],
        'unique' => [],
        'indexes' => [],
        'foreign_keys' => [],
        'auto_increment' => [],
    ];
}

function parseCreateTableBody(string $body): array
{
    $columns = [];
    $lines = preg_split('/\n/', trim($body)) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);
        $line = rtrim($line, ',');

        if ($line === '' || $line[0] !== '`') {
            continue;
        }

        if (!preg_match('/^`([^`]+)`\s+(.*)$/s', $line, $matches)) {
            continue;
        }

        $columnName = $matches[1];
        $definition = $matches[2];
        [$typeToken, $remainder] = splitTypeToken($definition);

        $columns[$columnName] = [
            'name' => $columnName,
            'sqlite_type' => mapSqliteType($typeToken),
            'not_null' => (bool) preg_match('/\bNOT NULL\b/i', $remainder),
            'default_sql' => extractDefaultSql($remainder),
        ];
    }

    return $columns;
}

function splitTypeToken(string $definition): array
{
    $length = strlen($definition);
    $depth = 0;

    for ($index = 0; $index < $length; $index++) {
        $char = $definition[$index];

        if ($char === '(') {
            $depth++;
            continue;
        }

        if ($char === ')') {
            $depth--;
            continue;
        }

        if ($depth === 0 && ctype_space($char)) {
            return [substr($definition, 0, $index), ltrim(substr($definition, $index + 1))];
        }
    }

    return [$definition, ''];
}

function mapSqliteType(string $typeToken): string
{
    $baseType = strtolower(preg_replace('/\(.*/', '', $typeToken) ?? $typeToken);

    return match ($baseType) {
        'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint' => 'INTEGER',
        'decimal', 'double', 'float', 'real', 'numeric' => 'NUMERIC',
        'char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'json' => 'TEXT',
        'date', 'datetime', 'timestamp', 'time' => 'TEXT',
        'blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary' => 'BLOB',
        default => 'TEXT',
    };
}

function extractDefaultSql(string $remainder): ?string
{
    if (!preg_match('/\bDEFAULT\b/i', $remainder, $matches, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $defaultStart = $matches[0][1] + strlen($matches[0][0]);
    $defaultTail = ltrim(substr($remainder, $defaultStart));

    if ($defaultTail === '') {
        return null;
    }

    if ($defaultTail[0] === "'") {
        return 'DEFAULT ' . convertSingleQuotedMySqlStringToSqlite($defaultTail);
    }

    if (!preg_match('/^([^\s,]+)/', $defaultTail, $valueMatches)) {
        return null;
    }

    $rawDefault = trim($valueMatches[1]);

    if (preg_match('/^current_timestamp(?:\(\))?$/i', $rawDefault)) {
        return 'DEFAULT CURRENT_TIMESTAMP';
    }

    if (strcasecmp($rawDefault, 'null') === 0) {
        return 'DEFAULT NULL';
    }

    return 'DEFAULT ' . $rawDefault;
}

function mergeTableMetadata(array &$tableMetadata, string $operations): void
{
    if (preg_match('/ADD PRIMARY KEY\s*\(([^)]+)\)/i', $operations, $matches)) {
        $tableMetadata['primary'] = parseColumnList($matches[1]);
    }

    if (preg_match_all('/ADD UNIQUE KEY\s+`([^`]+)`\s*\(([^)]+)\)/i', $operations, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tableMetadata['unique'][$match[1]] = parseColumnList($match[2]);
        }
    }

    if (preg_match_all('/ADD KEY\s+`([^`]+)`\s*\(([^)]+)\)/i', $operations, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tableMetadata['indexes'][$match[1]] = parseColumnList($match[2]);
        }
    }

    if (preg_match_all('/ADD CONSTRAINT\s+`([^`]+)`\s+FOREIGN KEY\s*\(([^)]+)\)\s+REFERENCES\s+`([^`]+)`\s*\(([^)]+)\)(?:\s+ON DELETE\s+(RESTRICT|CASCADE|SET NULL|NO ACTION))?(?:\s+ON UPDATE\s+(RESTRICT|CASCADE|SET NULL|NO ACTION))?/i', $operations, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tableMetadata['foreign_keys'][] = [
                'name' => $match[1],
                'columns' => parseColumnList($match[2]),
                'references_table' => $match[3],
                'references_columns' => parseColumnList($match[4]),
                'on_delete' => isset($match[5]) && $match[5] !== '' ? strtoupper($match[5]) : null,
                'on_update' => isset($match[6]) && $match[6] !== '' ? strtoupper($match[6]) : null,
            ];
        }
    }

    if (preg_match_all('/MODIFY\s+`([^`]+)`\s+[^,]*\bAUTO_INCREMENT\b/i', $operations, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $tableMetadata['auto_increment'][$match[1]] = true;
        }
    }
}

function parseColumnList(string $sql): array
{
    preg_match_all('/`([^`]+)`/', $sql, $matches);
    return $matches[1] ?? [];
}

function buildCreateTableSql(string $tableName, array $columns, array $metadata): string
{
    $definitions = [];
    $primaryColumns = $metadata['primary'];
    $singlePrimaryKey = count($primaryColumns) === 1 ? $primaryColumns[0] : null;
    $autoIncrementPrimaryKey = $singlePrimaryKey !== null && isset($metadata['auto_increment'][$singlePrimaryKey]);

    foreach ($columns as $column) {
        $line = quoteIdentifier($column['name']) . ' ';

        if ($autoIncrementPrimaryKey && $column['name'] === $singlePrimaryKey) {
            $line .= 'INTEGER PRIMARY KEY AUTOINCREMENT';
            $definitions[] = $line;
            continue;
        }

        $line .= $column['sqlite_type'];

        if ($singlePrimaryKey !== null && $column['name'] === $singlePrimaryKey) {
            $line .= ' PRIMARY KEY';
        }

        if ($column['not_null'] && $column['name'] !== $singlePrimaryKey) {
            $line .= ' NOT NULL';
        }

        if ($column['default_sql'] !== null) {
            $line .= ' ' . $column['default_sql'];
        }

        $definitions[] = $line;
    }

    if (count($primaryColumns) > 1) {
        $definitions[] = 'PRIMARY KEY (' . implode(', ', array_map('quoteIdentifier', $primaryColumns)) . ')';
    }

    foreach ($metadata['foreign_keys'] as $foreignKey) {
        $foreignKeySql = 'FOREIGN KEY (' . implode(', ', array_map('quoteIdentifier', $foreignKey['columns'])) . ') '
            . 'REFERENCES ' . quoteIdentifier($foreignKey['references_table'])
            . ' (' . implode(', ', array_map('quoteIdentifier', $foreignKey['references_columns'])) . ')';

        if ($foreignKey['on_delete'] !== null) {
            $foreignKeySql .= ' ON DELETE ' . $foreignKey['on_delete'];
        }

        if ($foreignKey['on_update'] !== null) {
            $foreignKeySql .= ' ON UPDATE ' . $foreignKey['on_update'];
        }

        $definitions[] = $foreignKeySql;
    }

    return 'CREATE TABLE ' . quoteIdentifier($tableName) . " (\n    " . implode(",\n    ", $definitions) . "\n)";
}

function buildIndexSql(string $tableName, array $metadata): array
{
    $sql = [];

    foreach ($metadata['unique'] as $indexName => $columns) {
        $sql[] = 'CREATE UNIQUE INDEX ' . quoteIdentifier($indexName)
            . ' ON ' . quoteIdentifier($tableName)
            . ' (' . implode(', ', array_map('quoteIdentifier', $columns)) . ')';
    }

    foreach ($metadata['indexes'] as $indexName => $columns) {
        $sql[] = 'CREATE INDEX ' . quoteIdentifier($indexName)
            . ' ON ' . quoteIdentifier($tableName)
            . ' (' . implode(', ', array_map('quoteIdentifier', $columns)) . ')';
    }

    return $sql;
}

function convertMySqlInsertToSqlite(string $statement): string
{
    $output = '';
    $length = strlen($statement);

    for ($index = 0; $index < $length; $index++) {
        $char = $statement[$index];

        if ($char !== "'") {
            $output .= $char;
            continue;
        }

        [$sqliteString, $nextIndex] = parseAndConvertSingleQuotedString($statement, $index);
        $output .= $sqliteString;
        $index = $nextIndex;
    }

    return $output;
}

function parseAndConvertSingleQuotedString(string $sql, int $startIndex): array
{
    $length = strlen($sql);
    $value = '';

    for ($index = $startIndex + 1; $index < $length; $index++) {
        $char = $sql[$index];

        if ($char === '\\') {
            if ($index + 1 >= $length) {
                break;
            }

            $nextChar = $sql[++$index];
            $value .= match ($nextChar) {
                '0' => "\0",
                'b' => "\x08",
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'Z' => chr(26),
                '\\' => '\\',
                "'" => "'",
                '"' => '"',
                default => $nextChar,
            };
            continue;
        }

        if ($char === "'") {
            if ($index + 1 < $length && $sql[$index + 1] === "'") {
                $value .= "'";
                $index++;
                continue;
            }

            return [quoteSqlString($value), $index];
        }

        $value .= $char;
    }

    return [quoteSqlString($value), $length - 1];
}

function convertSingleQuotedMySqlStringToSqlite(string $value): string
{
    [$converted] = parseAndConvertSingleQuotedString($value, 0);
    return $converted;
}

function quoteSqlString(string $value): string
{
    if (!str_contains($value, "\0")) {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    return "CAST(X'" . strtoupper(bin2hex($value)) . "' AS TEXT)";
}

function quoteIdentifier(string $value): string
{
    return '"' . str_replace('"', '""', $value) . '"';
}
