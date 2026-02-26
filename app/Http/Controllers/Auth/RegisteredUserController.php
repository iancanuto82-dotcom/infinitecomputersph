<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+$/'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+$/'],
            'phone' => ['required', 'string', 'min:7', 'max:20', 'regex:/^[0-9]+$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $firstName = trim((string) $validated['first_name']);
        $lastName = trim((string) $validated['last_name']);

        $user = User::create([
            'name' => trim($firstName.' '.$lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => (string) $validated['phone'],
            'email' => (string) $validated['email'],
            'password' => Hash::make((string) $validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $adminRouteName = AdminAccess::preferredAdminRouteName($user);
        $redirectTo = $adminRouteName
            ? route($adminRouteName, absolute: false)
            : route('home', absolute: false);

        return redirect($redirectTo);
    }
}
