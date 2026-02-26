<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $adminRouteName = AdminAccess::preferredAdminRouteName($request->user());
        $redirectTo = $adminRouteName
            ? route($adminRouteName, absolute: false)
            : route('home', absolute: false);

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($redirectTo.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($redirectTo.'?verified=1');
    }
}
