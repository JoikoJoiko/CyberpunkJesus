<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $credentials['email'] = mb_strtolower($credentials['email']);
        if (app()->isLocal() && $credentials['email'] === 'admin') {
            $credentials['email'] = 'tester@dossier.test';
        }
        validator($credentials, ['email' => ['email']])->validate();

        $key = 'login:'.sha1($credentials['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => "Слишком много попыток. Повторите через {$seconds} сек."]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['email' => 'Проверьте почту и пароль.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
