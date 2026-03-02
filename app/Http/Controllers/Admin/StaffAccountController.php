<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    public function index(): View
    {
        $staffUsers = User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.staff.index', [
            'staffUsers' => $staffUsers,
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'permissionGroups' => AdminAccess::staffPermissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'theme_preference' => ['required', Rule::in(['light', 'dark'])],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string'],
        ]);

        $permissions = AdminAccess::normalizeStaffPermissions((array) ($validated['permissions'] ?? []));

        if (count($permissions) === 0) {
            return back()
                ->withInput()
                ->withErrors(['permissions' => 'Choose at least one valid permission.']);
        }

        $staffUser = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => (string) $validated['password'],
            'role' => 'staff',
            'admin_permissions' => $permissions,
            'theme_preference' => (string) $validated['theme_preference'],
            'email_verified_at' => now(),
        ]);

        AuditLogger::record(
            $request,
            'created',
            'staff',
            (int) $staffUser->id,
            (string) $staffUser->name,
            'Created staff account.',
            [
                'email' => (string) $staffUser->email,
                'theme_preference' => (string) $staffUser->theme_preference,
                'permissions' => $permissions,
            ]
        );

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account created.');
    }

    public function edit(User $user): View
    {
        if (! $user->isStaff()) {
            abort(404);
        }

        return view('admin.staff.edit', [
            'staffUser' => $user,
            'permissionGroups' => AdminAccess::staffPermissionGroups(),
            'selectedPermissions' => AdminAccess::normalizeStaffPermissions((array) ($user->admin_permissions ?? [])),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $user->isStaff()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'theme_preference' => ['required', Rule::in(['light', 'dark'])],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string'],
        ]);

        $permissions = AdminAccess::normalizeStaffPermissions((array) ($validated['permissions'] ?? []));

        if (count($permissions) === 0) {
            return back()
                ->withInput()
                ->withErrors(['permissions' => 'Choose at least one valid permission.']);
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'staff',
            'admin_permissions' => $permissions,
            'theme_preference' => (string) $validated['theme_preference'],
        ];
        $before = $user->only(['name', 'email', 'admin_permissions', 'theme_preference']);

        if (! empty($validated['password'])) {
            $payload['password'] = (string) $validated['password'];
        }

        $user->update($payload);

        AuditLogger::record(
            $request,
            'updated',
            'staff',
            (int) $user->id,
            (string) $user->name,
            'Updated staff account.',
            [
                'before' => $before,
                'after' => $user->only(['name', 'email', 'admin_permissions', 'theme_preference']),
                'password_changed' => ! empty($validated['password']),
            ]
        );

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account updated.');
    }
}
