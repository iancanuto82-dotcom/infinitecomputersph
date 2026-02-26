<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetReader
{
    /**
     * @return array<int, array<int, string>>
     */
    public static function readRows(string $path, string $extension): array
    {
        $extension = strtolower(trim($extension));

        return match ($extension) {
            'csv', 'txt' => self::readCsvRows($path),
            'xlsx' => self::readXlsxRows($path),
            default => throw new InvalidArgumentException("Unsupported file extension: {$extension}"),
        };
    }

    /**
     * @return array<int, array<int, string>>
     */
    private static function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $rows[] = array_map(static fn ($value) => trim((string) $value), $row);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Minimal XLSX reader for simple sheets (first worksheet).
     *
     * @return array<int, array<int, string>>
     */
    private static function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open XLSX file.');
        }

        try {
            $sharedStrings = self::loadSharedStrings($zip);
            $worksheetPath = self::resolveFirstWorksheetPath($zip);

            $sheetXml = $zip->getFromName($worksheetPath);
            if ($sheetXml === false) {
                throw new RuntimeException('Unable to read worksheet from XLSX.');
            }

            $sheet = simplexml_load_string($sheetXml);
            if (! $sheet instanceof SimpleXMLElement) {
                throw new RuntimeException('Unable to parse worksheet XML.');
            }

            $rows = [];
            $sheetData = $sheet->sheetData ?? null;
            if (! $sheetData) {
                return [];
            }

            foreach ($sheetData->row as $rowEl) {
                $row = [];
                $maxCol = -1;

                foreach ($rowEl->c as $cell) {
                    $ref = (string) ($cell['r'] ?? '');
                    if ($ref === '' || ! preg_match('/^([A-Z]+)\d+$/', $ref, $m)) {
                        continue;
                    }

                    $colIndex = self::colLettersToIndex($m[1]);
                    $maxCol = max($maxCol, $colIndex);

                    $type = (string) ($cell['t'] ?? '');
                    $value = '';

                    if ($type === 's') {
                        $idx = (int) ($cell->v ?? 0);
                        $value = $sharedStrings[$idx] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = (string) ($cell->is->t ?? '');
                    } elseif ($type === 'str') {
                        $value = (string) ($cell->v ?? '');
                    } else {
                        $value = (string) ($cell->v ?? '');
                    }

                    $row[$colIndex] = trim($value);
                }

                if ($maxCol >= 0) {
                    for ($i = 0; $i <= $maxCol; $i++) {
                        $row[$i] = $row[$i] ?? '';
                    }

                    ksort($row);
                    $rows[] = array_values($row);
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function loadSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = simplexml_load_string($xml);
        if (! $doc instanceof SimpleXMLElement) {
            return [];
        }

        $strings = [];
        foreach ($doc->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }

            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) ($run->t ?? '');
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
                return 'xl/worksheets/sheet1.xml';
            }

            throw new RuntimeException('Unable to locate worksheet in XLSX.');
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $rels instanceof SimpleXMLElement) {
            throw new RuntimeException('Unable to parse workbook XML.');
        }

        $namespaces = $workbook->getNamespaces(true);
        $relNs = $namespaces['r'] ?? null;

        $firstSheet = $workbook->sheets->sheet[0] ?? null;
        if (! $firstSheet) {
            throw new RuntimeException('Workbook has no sheets.');
        }

        $rid = $relNs ? (string) $firstSheet->attributes($relNs)['id'] : (string) ($firstSheet['r:id'] ?? '');
        if ($rid === '') {
            if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
                return 'xl/worksheets/sheet1.xml';
            }
            throw new RuntimeException('Unable to resolve sheet relationship id.');
        }

        $rels->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $matches = $rels->xpath("//r:Relationship[@Id='{$rid}']");
        $target = $matches[0]['Target'] ?? null;

        $targetPath = $target ? (string) $target : '';
        if ($targetPath === '') {
            throw new RuntimeException('Unable to resolve worksheet target.');
        }

        $targetPath = ltrim($targetPath, '/');
        if (! str_starts_with($targetPath, 'xl/')) {
            $targetPath = 'xl/'.ltrim($targetPath, './');
        }

        if ($zip->locateName($targetPath) === false) {
            if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
                return 'xl/worksheets/sheet1.xml';
            }

            throw new RuntimeException('Worksheet XML not found inside XLSX.');
        }

        return $targetPath;
    }

    private static function colLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $num = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $num = ($num * 26) + (ord($letters[$i]) - 64);
        }
        return $num - 1;
    }
}

