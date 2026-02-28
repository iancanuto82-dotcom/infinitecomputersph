<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AuditLogController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const REVERTABLE_ACTIONS = ['updated', 'deleted', 'imported'];

    public function index(Request $request): View
    {
        $action = trim((string) $request->query('action', ''));
        $targetType = trim((string) $request->query('target_type', ''));
        $actorId = $request->integer('actor');
        $search = trim((string) $request->query('search', ''));
        $selectedDate = $this->normalizeDate(trim((string) $request->query('date', '')));

        $query = AuditLog::query()
            ->with('user')
            ->latest('id');

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($targetType !== '') {
            $query->where('target_type', $targetType);
        }

        if ($actorId > 0) {
            $query->where('user_id', $actorId);
        }

        if ($selectedDate !== null) {
            $query->whereDate('created_at', $selectedDate);
        }

        if ($search !== '') {
            $query->where(function ($logQuery) use ($search): void {
                $logQuery->where('description', 'like', "%{$search}%")
                    ->orWhere('target_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query
            ->paginate(30)
            ->withQueryString();
        $logs->getCollection()->transform(function (AuditLog $log) use ($request): AuditLog {
            $log->change_lines = $this->extractChangeLines((array) ($log->meta ?? []));
            $revertState = $this->resolveRevertState($log, $request->user());
            $log->setAttribute('has_revert_action', (bool) $revertState['has_revert_action']);
            $log->setAttribute('can_revert', (bool) $revertState['can_revert']);
            $log->setAttribute('is_reverted', (bool) $revertState['is_reverted']);
            $log->setAttribute('revert_reason', $revertState['reason']);
            return $log;
        });

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $targetTypes = AuditLog::query()
            ->select('target_type')
            ->distinct()
            ->orderBy('target_type')
            ->pluck('target_type');

        $actors = User::query()
            ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit.index', [
            'logs' => $logs,
            'actions' => $actions,
            'targetTypes' => $targetTypes,
            'actors' => $actors,
            'selectedAction' => $action,
            'selectedTargetType' => $targetType,
            'selectedActorId' => $actorId > 0 ? $actorId : null,
            'search' => $search,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function revert(Request $request, AuditLog $auditLog): RedirectResponse
    {
        $state = $this->resolveRevertState($auditLog, $request->user());

        if (! ($state['can_revert'] ?? false)) {
            return back()->with('error', (string) ($state['reason'] ?: 'This history record cannot be reverted.'));
        }

        try {
            DB::transaction(function () use ($request, $auditLog): void {
                $result = $this->applyRevert($auditLog);

                if (($result['flush_catalog'] ?? false) === true) {
                    $this->forgetPublicCatalogCaches();
                }

                $details = is_array($result['details'] ?? null)
                    ? $result['details']
                    : [];

                $meta = (array) ($auditLog->meta ?? []);
                $meta['reverted_at'] = now()->toIso8601String();
                $meta['reverted_by'] = (int) ($request->user()?->id ?? 0);
                $meta['revert_details'] = $details;
                $auditLog->meta = $meta;
                $auditLog->save();

                AuditLogger::record(
                    $request,
                    'reverted',
                    (string) $auditLog->target_type,
                    $auditLog->target_id !== null ? (int) $auditLog->target_id : null,
                    $auditLog->target_name ? (string) $auditLog->target_name : null,
                    sprintf(
                        'Reverted %s record from history log #%d.',
                        str_replace('_', ' ', (string) $auditLog->action),
                        (int) $auditLog->id
                    ),
                    [
                        'source_audit_log_id' => (int) $auditLog->id,
                        'source_action' => (string) $auditLog->action,
                        'source_target_type' => (string) $auditLog->target_type,
                        'details' => $details,
                    ]
                );
            });
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable) {
            return back()->with('error', 'Unable to revert this history record.');
        }

        return back()->with('status', 'History record reverted.');
    }

    private function normalizeDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<int, string>
     */
    private function extractChangeLines(array $meta): array
    {
        $changes = [];

        $before = $meta['before'] ?? null;
        $after = $meta['after'] ?? null;

        if (is_array($before) && is_array($after)) {
            $keys = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));
            foreach ($keys as $key) {
                $oldValue = $before[$key] ?? null;
                $newValue = $after[$key] ?? null;

                if ($this->metaValuesEqual($oldValue, $newValue)) {
                    continue;
                }

                $changes[] = sprintf(
                    '%s: %s -> %s',
                    $this->humanizeKey((string) $key),
                    $this->formatMetaValue($oldValue),
                    $this->formatMetaValue($newValue)
                );
            }
        }

        foreach ($meta as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'before_')) {
                continue;
            }

            $suffix = substr($key, 7);
            if ($suffix === '') {
                continue;
            }

            $afterKey = 'after_'.$suffix;
            if (! array_key_exists($afterKey, $meta)) {
                continue;
            }

            $newValue = $meta[$afterKey];
            if ($this->metaValuesEqual($value, $newValue)) {
                continue;
            }

            $changes[] = sprintf(
                '%s: %s -> %s',
                $this->humanizeKey($suffix),
                $this->formatMetaValue($value),
                $this->formatMetaValue($newValue)
            );
        }

        $changes = array_values(array_unique($changes));
        if ($changes !== []) {
            return array_slice($changes, 0, 8);
        }

        $flat = $this->flattenMeta($meta);
        foreach ($flat as $key => $value) {
            if ($value === null || is_array($value)) {
                continue;
            }

            $valueString = trim($this->formatMetaValue($value));
            if ($valueString === '' || $valueString === '--') {
                continue;
            }

            $changes[] = sprintf('%s: %s', $this->humanizeKey($key), $valueString);
        }

        return array_slice($changes, 0, 6);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function flattenMeta(array $meta, string $prefix = ''): array
    {
        $flat = [];

        foreach ($meta as $key => $value) {
            $stringKey = (string) $key;
            $path = $prefix !== '' ? $prefix.'.'.$stringKey : $stringKey;

            if (is_array($value)) {
                $flat += $this->flattenMeta($value, $path);
                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    private function humanizeKey(string $key): string
    {
        return Str::of($key)
            ->replace(['.', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    /**
     * @param mixed $value
     */
    private function formatMetaValue($value): string
    {
        if ($value === null) {
            return '--';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return '--';
            }

            return Str::limit($trimmed, 90);
        }

        if (is_array($value)) {
            $allScalar = collect($value)->every(fn ($entry) => is_scalar($entry) || $entry === null);
            if ($allScalar) {
                $joined = implode(', ', array_map(function ($entry): string {
                    if ($entry === null) {
                        return '--';
                    }
                    if (is_bool($entry)) {
                        return $entry ? 'Yes' : 'No';
                    }

                    return (string) $entry;
                }, $value));

                return Str::limit($joined, 90);
            }

            return Str::limit((string) json_encode($value), 90);
        }

        return Str::limit((string) $value, 90);
    }

    /**
     * @param mixed $left
     * @param mixed $right
     */
    private function metaValuesEqual($left, $right): bool
    {
        return json_encode($left) === json_encode($right);
    }

    /**
     * @return array{has_revert_action: bool, can_revert: bool, is_reverted: bool, reason: ?string}
     */
    private function resolveRevertState(AuditLog $log, ?Authenticatable $user): array
    {
        $action = trim((string) $log->action);
        $hasRevertAction = in_array($action, self::REVERTABLE_ACTIONS, true);
        $meta = (array) ($log->meta ?? []);
        $isReverted = $this->isAlreadyReverted($meta);

        if (! $hasRevertAction) {
            return [
                'has_revert_action' => false,
                'can_revert' => false,
                'is_reverted' => $isReverted,
                'reason' => null,
            ];
        }

        if ($isReverted) {
            return [
                'has_revert_action' => true,
                'can_revert' => false,
                'is_reverted' => true,
                'reason' => 'Already reverted.',
            ];
        }

        $permission = $this->requiredPermissionForLog($log);
        if ($permission !== null && ! AdminAccess::hasPermission($user, $permission)) {
            return [
                'has_revert_action' => true,
                'can_revert' => false,
                'is_reverted' => false,
                'reason' => 'Missing permission: '.$permission,
            ];
        }

        $reason = match ($action) {
            'updated' => $this->updatedRevertReason($log, $meta),
            'deleted' => $this->deletedRevertReason($log, $meta),
            'imported' => $this->importedRevertReason($log, $meta),
            default => 'Unsupported action.',
        };

        return [
            'has_revert_action' => true,
            'can_revert' => $reason === null,
            'is_reverted' => false,
            'reason' => $reason,
        ];
    }

    private function requiredPermissionForLog(AuditLog $log): ?string
    {
        return match ((string) $log->target_type) {
            'product' => 'products.edit',
            'category' => 'categories.edit',
            'staff' => 'users.manage',
            'expense' => 'sales.edit',
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function isAlreadyReverted(array $meta): bool
    {
        return isset($meta['reverted_at']) && trim((string) $meta['reverted_at']) !== '';
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function updatedRevertReason(AuditLog $log, array $meta): ?string
    {
        $targetType = (string) $log->target_type;
        $targetId = (int) ($log->target_id ?? 0);

        if ($targetId <= 0) {
            return 'Missing target record ID.';
        }

        return match ($targetType) {
            'product' => $this->updatedProductRevertReason($targetId, $meta),
            'category' => $this->updatedCategoryRevertReason($targetId, $meta),
            'staff' => $this->updatedStaffRevertReason($targetId, $meta),
            default => 'Updated revert is only supported for product, category, and staff.',
        };
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function updatedProductRevertReason(int $targetId, array $meta): ?string
    {
        if (! Product::query()->whereKey($targetId)->exists()) {
            return 'Product record no longer exists.';
        }

        $before = is_array($meta['before'] ?? null) ? (array) $meta['before'] : null;
        if (! $before) {
            return 'Missing previous values in audit metadata.';
        }

        if (array_key_exists('category_id', $before)) {
            $beforeCategoryId = (int) $before['category_id'];
            if ($beforeCategoryId > 0 && ! Category::query()->whereKey($beforeCategoryId)->exists()) {
                return 'Previous category no longer exists.';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function updatedCategoryRevertReason(int $targetId, array $meta): ?string
    {
        if (! Category::query()->whereKey($targetId)->exists()) {
            return 'Category record no longer exists.';
        }

        $before = is_array($meta['before'] ?? null) ? (array) $meta['before'] : [];
        $beforeName = trim((string) ($before['name'] ?? ($meta['before_name'] ?? '')));
        if ($beforeName === '') {
            return 'Missing previous category name in audit metadata.';
        }

        if (array_key_exists('parent_id', $before) && $before['parent_id'] !== null) {
            $parentId = (int) $before['parent_id'];
            if ($parentId > 0 && ! Category::query()->whereKey($parentId)->exists()) {
                return 'Previous parent category no longer exists.';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function updatedStaffRevertReason(int $targetId, array $meta): ?string
    {
        $staff = User::query()->find($targetId);
        if (! $staff || ! $staff->isStaff()) {
            return 'Staff record no longer exists.';
        }

        $before = is_array($meta['before'] ?? null) ? (array) $meta['before'] : null;
        if (! $before) {
            return 'Missing previous values in audit metadata.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function deletedRevertReason(AuditLog $log, array $meta): ?string
    {
        $targetType = (string) $log->target_type;
        $targetId = (int) ($log->target_id ?? 0);

        return match ($targetType) {
            'product' => $this->deletedProductRevertReason($targetId, $log, $meta),
            'category' => $this->deletedCategoryRevertReason($targetId, $log, $meta),
            'expense' => $this->deletedExpenseRevertReason($targetId, $log, $meta),
            default => 'Deleted revert is only supported for product, category, and expense.',
        };
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function deletedProductRevertReason(int $targetId, AuditLog $log, array $meta): ?string
    {
        if ($targetId > 0 && Product::query()->whereKey($targetId)->exists()) {
            return 'Product already exists. Nothing to restore.';
        }

        $snapshot = $this->extractDeletedSnapshot($meta);
        if ($snapshot === null) {
            return 'Missing deleted snapshot metadata for this product.';
        }

        $name = trim((string) ($snapshot['name'] ?? $log->target_name ?? ''));
        if ($name === '') {
            return 'Missing product name in deleted snapshot.';
        }

        $categoryId = (int) ($snapshot['category_id'] ?? 0);
        if ($categoryId <= 0) {
            return 'Missing product category in deleted snapshot.';
        }

        if (! Category::query()->whereKey($categoryId)->exists()) {
            return 'Product category no longer exists.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function deletedCategoryRevertReason(int $targetId, AuditLog $log, array $meta): ?string
    {
        if ($targetId > 0 && Category::query()->whereKey($targetId)->exists()) {
            return 'Category already exists. Nothing to restore.';
        }

        $snapshot = $this->extractDeletedSnapshot($meta);
        if ($snapshot === null) {
            return 'Missing deleted snapshot metadata for this category.';
        }

        $name = trim((string) ($snapshot['name'] ?? $log->target_name ?? ''));
        if ($name === '') {
            return 'Missing category name in deleted snapshot.';
        }

        $parentId = $snapshot['parent_id'] ?? null;
        if ($parentId !== null && (int) $parentId > 0 && ! Category::query()->whereKey((int) $parentId)->exists()) {
            return 'Parent category no longer exists.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function deletedExpenseRevertReason(int $targetId, AuditLog $log, array $meta): ?string
    {
        if ($targetId > 0 && Expense::query()->whereKey($targetId)->exists()) {
            return 'Expense already exists. Nothing to restore.';
        }

        $snapshot = $this->extractDeletedSnapshot($meta);
        if ($snapshot === null) {
            return 'Missing deleted snapshot metadata for this expense.';
        }

        $title = trim((string) ($snapshot['title'] ?? $log->target_name ?? ''));
        if ($title === '') {
            return 'Missing expense title in deleted snapshot.';
        }

        $amountRaw = $snapshot['amount'] ?? null;
        if ($amountRaw === null || ! is_numeric($amountRaw)) {
            return 'Missing expense amount in deleted snapshot.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function importedRevertReason(AuditLog $log, array $meta): ?string
    {
        if ((string) $log->target_type !== 'product') {
            return 'Imported revert is only supported for product imports.';
        }

        $revert = $this->extractImportRevertData($meta);
        if ($revert === null) {
            return 'Missing import revert metadata for this log.';
        }

        if ($revert['created_product_ids'] === [] && $revert['updated_products'] === []) {
            return 'No product changes were stored for this import.';
        }

        return null;
    }

    /**
     * @return array{details: array<string, mixed>, flush_catalog: bool}
     */
    private function applyRevert(AuditLog $log): array
    {
        $meta = (array) ($log->meta ?? []);

        return match ((string) $log->action) {
            'updated' => [
                'details' => $this->revertUpdated($log, $meta),
                'flush_catalog' => in_array((string) $log->target_type, ['product', 'category'], true),
            ],
            'deleted' => [
                'details' => $this->revertDeleted($log, $meta),
                'flush_catalog' => in_array((string) $log->target_type, ['product', 'category'], true),
            ],
            'imported' => [
                'details' => $this->revertImported($meta),
                'flush_catalog' => true,
            ],
            default => throw new RuntimeException('This action cannot be reverted.'),
        };
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function revertUpdated(AuditLog $log, array $meta): array
    {
        $targetType = (string) $log->target_type;
        $targetId = (int) ($log->target_id ?? 0);

        if ($targetId <= 0) {
            throw new RuntimeException('Missing target ID for updated revert.');
        }

        if ($targetType === 'product') {
            $product = Product::query()->find($targetId);
            if (! $product) {
                throw new RuntimeException('Product record no longer exists.');
            }

            $before = is_array($meta['before'] ?? null) ? (array) $meta['before'] : [];
            $payload = $this->normalizeProductPayload($before);
            if ($payload === []) {
                throw new RuntimeException('No previous product values to restore.');
            }

            if (isset($payload['category_id']) && ! Category::query()->whereKey((int) $payload['category_id'])->exists()) {
                throw new RuntimeException('Previous product category no longer exists.');
            }

            $product->update($payload);

            return [
                'target_type' => 'product',
                'target_id' => (int) $product->id,
                'restored_fields' => array_values(array_keys($payload)),
            ];
        }

        if ($targetType === 'category') {
            $category = Category::query()->find($targetId);
            if (! $category) {
                throw new RuntimeException('Category record no longer exists.');
            }

            $before = is_array($meta['before'] ?? null) ? (array) $meta['before'] : [];
            $payload = [];
            $beforeName = trim((string) ($before['name'] ?? ($meta['before_name'] ?? '')));
            if ($beforeName !== '') {
                $payload['name'] = $beforeName;
            }

            if (array_key_exists('parent_id', $before)) {
                $parentId = $before['parent_id'] !== null ? (int) $before['parent_id'] : null;
                if ($parentId !== null && $parentId > 0 && ! Category::query()->whereKey($parentId)->exists()) {
                    throw new RuntimeException('Previous parent category no longer exists.');
                }
                $payload['parent_id'] = $parentId;
            }

            if ($payload === []) {
                throw new RuntimeException('No previous category values to restore.');
            }

            $category->update($payload);

            return [
                'target_type' => 'category',
                'target_id' => (int) $category->id,
                'restored_fields' => array_values(array_keys($payload)),
            ];
        }

        if ($targetType === 'staff') {
            $staff = User::query()->find($targetId);
            if (! $staff || ! $staff->isStaff()) {
                throw new RuntimeException('Staff record no longer exists.');
            }

            $before = is_array($meta['before'] ?? null) ? (array) $meta['before'] : [];
            $payload = [];

            if (array_key_exists('name', $before)) {
                $name = trim((string) $before['name']);
                if ($name !== '') {
                    $payload['name'] = $name;
                }
            }

            if (array_key_exists('email', $before)) {
                $email = trim((string) $before['email']);
                if ($email !== '') {
                    $payload['email'] = $email;
                }
            }

            if (array_key_exists('admin_permissions', $before) && is_array($before['admin_permissions'])) {
                $payload['admin_permissions'] = (array) $before['admin_permissions'];
            }

            if ($payload === []) {
                throw new RuntimeException('No previous staff values to restore.');
            }

            $staff->update($payload);

            return [
                'target_type' => 'staff',
                'target_id' => (int) $staff->id,
                'restored_fields' => array_values(array_keys($payload)),
            ];
        }

        throw new RuntimeException('Updated revert is not supported for this module.');
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function revertDeleted(AuditLog $log, array $meta): array
    {
        $targetType = (string) $log->target_type;
        $targetId = (int) ($log->target_id ?? 0);
        $snapshot = $this->extractDeletedSnapshot($meta);

        if ($snapshot === null) {
            throw new RuntimeException('Missing deleted snapshot metadata.');
        }

        if ($targetType === 'product') {
            if ($targetId > 0 && Product::query()->whereKey($targetId)->exists()) {
                throw new RuntimeException('Product already exists. Nothing to restore.');
            }

            $payload = $this->normalizeProductPayload($snapshot);
            $payload['name'] = trim((string) ($payload['name'] ?? ($log->target_name ?? '')));

            if ($payload['name'] === '') {
                throw new RuntimeException('Missing product name in deleted snapshot.');
            }

            if (! isset($payload['category_id'])) {
                throw new RuntimeException('Missing product category in deleted snapshot.');
            }

            $categoryId = (int) $payload['category_id'];
            if (! Category::query()->whereKey($categoryId)->exists()) {
                throw new RuntimeException('Product category no longer exists.');
            }

            if (! isset($payload['price'])) {
                $payload['price'] = 0;
            }

            if (! isset($payload['stock'])) {
                $payload['stock'] = max(0, (int) ($meta['stock'] ?? 0));
            }

            if (! isset($payload['initial_stock'])) {
                $payload['initial_stock'] = max(0, (int) $payload['stock']);
            }

            if (! array_key_exists('is_active', $payload)) {
                $payload['is_active'] = true;
            }

            $insert = [
                'name' => (string) $payload['name'],
                'description' => $payload['description'] ?? null,
                'image_path' => $payload['image_path'] ?? null,
                'image_url' => $payload['image_url'] ?? null,
                'price' => round((float) $payload['price'], 2),
                'cost_price' => array_key_exists('cost_price', $payload) && $payload['cost_price'] !== null
                    ? round((float) $payload['cost_price'], 2)
                    : null,
                'initial_stock' => max(0, (int) $payload['initial_stock']),
                'stock' => (int) $payload['stock'],
                'category_id' => $categoryId,
                'is_active' => (bool) $payload['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($targetId > 0) {
                $insert['id'] = $targetId;
            }

            DB::table('products')->insert($insert);

            return [
                'target_type' => 'product',
                'restored_id' => (int) ($insert['id'] ?? Product::query()->latest('id')->value('id')),
                'mode' => 'recreated_deleted_record',
            ];
        }

        if ($targetType === 'category') {
            if ($targetId > 0 && Category::query()->whereKey($targetId)->exists()) {
                throw new RuntimeException('Category already exists. Nothing to restore.');
            }

            $name = trim((string) ($snapshot['name'] ?? $log->target_name ?? ''));
            if ($name === '') {
                throw new RuntimeException('Missing category name in deleted snapshot.');
            }

            $parentId = $snapshot['parent_id'] ?? null;
            $normalizedParentId = $parentId !== null ? (int) $parentId : null;
            if ($normalizedParentId !== null && $normalizedParentId > 0 && ! Category::query()->whereKey($normalizedParentId)->exists()) {
                throw new RuntimeException('Parent category no longer exists.');
            }

            $insert = [
                'name' => $name,
                'parent_id' => $normalizedParentId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($targetId > 0) {
                $insert['id'] = $targetId;
            }

            DB::table('categories')->insert($insert);

            return [
                'target_type' => 'category',
                'restored_id' => (int) ($insert['id'] ?? Category::query()->latest('id')->value('id')),
                'mode' => 'recreated_deleted_record',
            ];
        }

        if ($targetType === 'expense') {
            if ($targetId > 0 && Expense::query()->whereKey($targetId)->exists()) {
                throw new RuntimeException('Expense already exists. Nothing to restore.');
            }

            $title = trim((string) ($snapshot['title'] ?? $log->target_name ?? ''));
            if ($title === '') {
                throw new RuntimeException('Missing expense title in deleted snapshot.');
            }

            $amount = $snapshot['amount'] ?? ($meta['amount'] ?? null);
            if ($amount === null || ! is_numeric($amount)) {
                throw new RuntimeException('Missing expense amount in deleted snapshot.');
            }

            $spentAt = $snapshot['spent_at'] ?? ($meta['spent_at'] ?? null);
            if (is_string($spentAt) && trim($spentAt) !== '') {
                try {
                    $spentAtValue = Carbon::parse($spentAt);
                } catch (\Throwable) {
                    $spentAtValue = now();
                }
            } else {
                $spentAtValue = now();
            }

            $insert = [
                'created_by' => isset($snapshot['created_by']) && $snapshot['created_by'] !== null ? (int) $snapshot['created_by'] : null,
                'spent_at' => $spentAtValue,
                'title' => $title,
                'category' => isset($snapshot['category']) ? trim((string) $snapshot['category']) : null,
                'amount' => round((float) $amount, 2),
                'notes' => isset($snapshot['notes']) ? trim((string) $snapshot['notes']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($targetId > 0) {
                $insert['id'] = $targetId;
            }

            DB::table('expenses')->insert($insert);

            return [
                'target_type' => 'expense',
                'restored_id' => (int) ($insert['id'] ?? Expense::query()->latest('id')->value('id')),
                'mode' => 'recreated_deleted_record',
            ];
        }

        throw new RuntimeException('Deleted revert is not supported for this module.');
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function revertImported(array $meta): array
    {
        $revertData = $this->extractImportRevertData($meta);
        if ($revertData === null) {
            throw new RuntimeException('Missing import revert metadata.');
        }

        $deletedCreated = 0;
        $missingCreated = 0;
        $restoredUpdated = 0;
        $missingUpdated = 0;
        $skippedMissingCategory = 0;

        $createdProductIds = $revertData['created_product_ids'];
        if ($createdProductIds !== []) {
            $createdProducts = Product::query()
                ->whereIn('id', $createdProductIds)
                ->get()
                ->keyBy('id');

            foreach ($createdProductIds as $productId) {
                $createdProduct = $createdProducts->get($productId);
                if (! $createdProduct) {
                    $missingCreated++;
                    continue;
                }

                $createdProduct->delete();
                $deletedCreated++;
            }
        }

        $updatedProducts = $revertData['updated_products'];
        foreach ($updatedProducts as $row) {
            $productId = (int) ($row['id'] ?? 0);
            $before = is_array($row['before'] ?? null) ? (array) $row['before'] : [];
            if ($productId <= 0 || $before === []) {
                $missingUpdated++;
                continue;
            }

            $product = Product::query()->find($productId);
            if (! $product) {
                $missingUpdated++;
                continue;
            }

            $payload = $this->normalizeProductPayload($before);
            if ($payload === []) {
                $missingUpdated++;
                continue;
            }

            if (array_key_exists('category_id', $payload) && ! Category::query()->whereKey((int) $payload['category_id'])->exists()) {
                $skippedMissingCategory++;
                continue;
            }

            $product->update($payload);
            $restoredUpdated++;
        }

        if ($deletedCreated === 0 && $restoredUpdated === 0) {
            throw new RuntimeException('No imported products were reverted. Records may already be gone.');
        }

        return [
            'target_type' => 'product',
            'deleted_created_products' => $deletedCreated,
            'restored_updated_products' => $restoredUpdated,
            'missing_created_products' => $missingCreated,
            'missing_updated_products' => $missingUpdated,
            'skipped_missing_category' => $skippedMissingCategory,
        ];
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>|null
     */
    private function extractDeletedSnapshot(array $meta): ?array
    {
        $snapshot = $meta['deleted_snapshot'] ?? null;
        if (is_array($snapshot)) {
            return $snapshot;
        }

        $snapshot = $meta['snapshot'] ?? null;
        if (is_array($snapshot)) {
            return $snapshot;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $meta
     * @return array{created_product_ids: array<int, int>, updated_products: array<int, array{id: int, before: array<string, mixed>}>}|null
     */
    private function extractImportRevertData(array $meta): ?array
    {
        $revert = $meta['revert'] ?? null;
        if (! is_array($revert)) {
            return null;
        }

        $createdProductIds = [];
        foreach ((array) ($revert['created_product_ids'] ?? []) as $id) {
            $productId = (int) $id;
            if ($productId > 0) {
                $createdProductIds[] = $productId;
            }
        }
        $createdProductIds = array_values(array_unique($createdProductIds));

        $updatedProducts = [];
        foreach ((array) ($revert['updated_products'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $productId = (int) ($row['id'] ?? 0);
            $before = is_array($row['before'] ?? null) ? (array) $row['before'] : null;
            if ($productId <= 0 || $before === null) {
                continue;
            }

            $updatedProducts[] = [
                'id' => $productId,
                'before' => $before,
            ];
        }

        return [
            'created_product_ids' => $createdProductIds,
            'updated_products' => $updatedProducts,
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function normalizeProductPayload(array $source): array
    {
        $payload = [];

        if (array_key_exists('name', $source)) {
            $name = trim((string) $source['name']);
            if ($name !== '') {
                $payload['name'] = $name;
            }
        }

        if (array_key_exists('description', $source)) {
            $payload['description'] = $source['description'] !== null
                ? trim((string) $source['description'])
                : null;
        }

        if (array_key_exists('image_path', $source)) {
            $payload['image_path'] = $source['image_path'] !== null
                ? trim((string) $source['image_path'])
                : null;
        }

        if (array_key_exists('image_url', $source)) {
            $payload['image_url'] = $source['image_url'] !== null
                ? trim((string) $source['image_url'])
                : null;
        }

        if (array_key_exists('price', $source) && is_numeric($source['price'])) {
            $payload['price'] = round((float) $source['price'], 2);
        }

        if (array_key_exists('cost_price', $source)) {
            $payload['cost_price'] = $source['cost_price'] === null || $source['cost_price'] === ''
                ? null
                : round((float) $source['cost_price'], 2);
        }

        if (array_key_exists('initial_stock', $source)) {
            $payload['initial_stock'] = max(0, (int) $source['initial_stock']);
        }

        if (array_key_exists('stock', $source)) {
            $payload['stock'] = (int) $source['stock'];
        }

        if (array_key_exists('category_id', $source)) {
            $categoryId = (int) $source['category_id'];
            if ($categoryId > 0) {
                $payload['category_id'] = $categoryId;
            }
        }

        if (array_key_exists('is_active', $source)) {
            $payload['is_active'] = (bool) $source['is_active'];
        }

        return $payload;
    }

    private function forgetPublicCatalogCaches(): void
    {
        Cache::forget('public_categories_list_v1');
        Cache::forget('public_categories_list_v2');
        Cache::forget('public_landing_data_v1');
        Cache::forget('public_landing_data_v2');
    }
}
