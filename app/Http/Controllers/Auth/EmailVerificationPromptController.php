<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $adminRouteName = AdminAccess::preferredAdminRouteName($request->user());
        $redirectTo = $adminRouteName
            ? route($adminRouteName, absolute: false)
            : route('home', absolute: false);

        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended($redirectTo)
                    : view('auth.verify-email');
    }
}
