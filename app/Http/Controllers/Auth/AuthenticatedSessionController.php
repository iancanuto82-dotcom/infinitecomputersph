<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $isAdmin = AdminAccess::isAdmin($user);

        $intended = (string) $request->session()->get('url.intended', '');
        $intendedPath = (string) (parse_url($intended, PHP_URL_PATH) ?? '');

        if (! $isAdmin && (str_starts_with($intendedPath, '/admin') || $intendedPath === '/dashboard')) {
            $request->session()->forget('url.intended');
        }

        if ($isAdmin && $intendedPath !== '' && ! str_starts_with($intendedPath, '/admin')) {
            $request->session()->forget('url.intended');
        }

        if ($isAdmin && str_starts_with($intendedPath, '/admin')) {
            $requiredPermission = $this->requiredPermissionForAdminPath($intendedPath);

            if ($requiredPermission === null || ! AdminAccess::hasPermission($user, $requiredPermission)) {
                $request->session()->forget('url.intended');
            }
        }

        $defaultRoute = route('home', absolute: false);

        if ($isAdmin) {
            $adminRouteName = AdminAccess::hasPermission($user, 'dashboard.view')
                ? 'admin.dashboard'
                : AdminAccess::preferredAdminRouteName($user);

            if ($adminRouteName) {
                $defaultRoute = route($adminRouteName, absolute: false);
            }
        }

        return redirect()->intended($defaultRoute);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function requiredPermissionForAdminPath(string $path): ?string
    {
        if (str_starts_with($path, '/admin/staff')) {
            return 'users.manage';
        }

        if (str_starts_with($path, '/admin/products')) {
            return 'products.view';
        }

        if (str_starts_with($path, '/admin/categories')) {
            return 'categories.view';
        }

        if (str_starts_with($path, '/admin/sales')) {
            return 'sales.view';
        }

        if (str_starts_with($path, '/admin/pc-builder')) {
            return 'pc_builder.view';
        }

        if (str_starts_with($path, '/admin/content')) {
            return 'content.view';
        }

        if ($path === '/admin/dashboard' || $path === '/admin') {
            return 'dashboard.view';
        }

        return null;
    }
}
