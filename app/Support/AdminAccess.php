<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class AdminAccess
{
    /**
     * @var array<string, array{label: string, permissions: array<string, string>}>
     */
    private const STAFF_PERMISSION_GROUPS = [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => [
                'dashboard.view' => 'View dashboard',
            ],
        ],
        'products' => [
            'label' => 'Products',
            'permissions' => [
                'products.view' => 'View products',
                'products.cost_stock.view' => 'View product cost and initial stock',
                'products.edit' => 'Create, import, update, and delete products',
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'permissions' => [
                'categories.view' => 'View categories',
                'categories.edit' => 'Create, update, and delete categories',
            ],
        ],
        'sales' => [
            'label' => 'Sales',
            'permissions' => [
                'sales.view' => 'View sales and quotations',
                'sales.edit' => 'Create sales and update payment/status',
            ],
        ],
        'pc_builder' => [
            'label' => 'PC Builder',
            'permissions' => [
                'pc_builder.view' => 'View PC Builder and quotation history',
                'pc_builder.edit' => 'Create quotations and add to sales',
            ],
        ],
        'content' => [
            'label' => 'Website Content',
            'permissions' => [
                'content.view' => 'View carousel and featured brands settings',
                'content.edit' => 'Update carousel and featured brands settings',
            ],
        ],
        'history' => [
            'label' => 'History',
            'permissions' => [
                'audit.view' => 'View action history logs',
            ],
        ],
    ];

    /**
     * @var array<string, string|array<int, string>>
     */
    private const IMPLIED_PERMISSIONS = [
        'products.edit' => ['products.view', 'products.cost_stock.view'],
        'categories.edit' => 'categories.view',
        'sales.edit' => 'sales.view',
        'pc_builder.edit' => 'pc_builder.view',
        'content.edit' => 'content.view',
    ];

    public static function isAdmin(?Authenticatable $user): bool
    {
        return self::isOwner($user) || self::isStaff($user);
    }

    public static function isOwner(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        $role = Str::lower(trim((string) ($user->role ?? '')));

        if ($role !== '') {
            return $role === 'owner';
        }

        $name = Str::lower(trim((string) ($user->name ?? '')));
        $email = Str::lower(trim((string) ($user->email ?? '')));

        if ($name === 'admin') {
            return true;
        }

        if ($email === 'admin') {
            return true;
        }

        return Str::startsWith($email, 'admin@');
    }

    public static function isStaff(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        return Str::lower(trim((string) ($user->role ?? ''))) === 'staff';
    }

    public static function hasPermission(?Authenticatable $user, string $permission): bool
    {
        if (! self::isAdmin($user)) {
            return false;
        }

        if (self::isOwner($user)) {
            return true;
        }

        if ($permission === 'users.manage') {
            return false;
        }

        $permissions = self::normalizeStaffPermissions((array) ($user->admin_permissions ?? []));

        return in_array($permission, $permissions, true);
    }

    public static function preferredAdminRouteName(?Authenticatable $user): ?string
    {
        if (! self::isAdmin($user)) {
            return null;
        }

        $map = [
            'dashboard.view' => 'admin.dashboard',
            'sales.view' => 'admin.sales',
            'pc_builder.view' => 'admin.pc-builder',
            'products.view' => 'admin.products.index',
            'categories.view' => 'admin.categories.index',
            'content.view' => 'admin.content.edit',
            'audit.view' => 'admin.audit.index',
            'users.manage' => 'admin.staff.index',
        ];

        foreach ($map as $permission => $routeName) {
            if (self::hasPermission($user, $permission)) {
                return $routeName;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function staffPermissionGroups(): array
    {
        return self::STAFF_PERMISSION_GROUPS;
    }

    /**
     * @return array<int, string>
     */
    public static function staffPermissionKeys(): array
    {
        $keys = [];

        foreach (self::STAFF_PERMISSION_GROUPS as $group) {
            $keys = [...$keys, ...array_keys($group['permissions'])];
        }

        return $keys;
    }

    /**
     * @param array<int, string> $permissions
     * @return array<int, string>
     */
    public static function normalizeStaffPermissions(array $permissions): array
    {
        $allowed = array_flip(self::staffPermissionKeys());
        $normalized = [];

        foreach ($permissions as $permission) {
            $key = Str::lower(trim((string) $permission));

            if ($key === '' || ! Arr::exists($allowed, $key)) {
                continue;
            }

            $normalized[] = $key;
        }

        $normalized = array_values(array_unique($normalized));

        foreach (self::IMPLIED_PERMISSIONS as $child => $parents) {
            if (! in_array($child, $normalized, true)) {
                continue;
            }

            $parentList = is_array($parents) ? $parents : [$parents];
            foreach ($parentList as $parent) {
                if (! in_array($parent, $normalized, true)) {
                    $normalized[] = $parent;
                }
            }
        }

        sort($normalized);

        return $normalized;
    }
}
