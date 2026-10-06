<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.login');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'mobile' => $validated['mobile'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'password' => $validated['password'],
        ]);

        Auth::guard('web')->login($user);
        $request->session()->forget('url.intended');
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Your account has been created successfully.');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $key = 'user-login:'.Str::lower($validated['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $user = User::where('email', Str::lower($validated['email']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        if ($user->role !== 'user' || $user->account_status !== 'active') {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'Unable to sign in with these credentials.']);
        }

        RateLimiter::clear($key);
        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->forget('url.intended');
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Signed in successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Signed out successfully.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => Str::lower($request->string('email')->toString())]);

        return back()->with('status', 'If an account matches that address, a password reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if (User::where('email', Str::lower($validated['email']))->where('role', 'admin')->exists()) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This reset link is not valid for a user account.',
            ]);
        }

        $status = Password::reset($validated, function (User $user, string $password): void {
            abort_unless($user->role === 'user', 403);

            DB::transaction(function () use ($user, $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                AuditLog::create([
                    'subject_user_id' => $user->id,
                    'event' => 'user.password_reset',
                    'metadata' => [],
                ]);
            });
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    public function profile(Request $request): View
    {
        $user = $request->user('web');

        return view('pages.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $request->user('web')->update($validated);
            AuditLog::create([
                'actor_user_id' => $request->user('web')->id,
                'subject_user_id' => $request->user('web')->id,
                'event' => 'user.profile_updated',
                'metadata' => [],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Profile updated.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user('web');
        DB::transaction(function () use ($user, $validated, $request): void {
            $user->forceFill([
                'password' => $validated['password'],
                'remember_token' => Str::random(60),
            ])->save();
            AuditLog::create([
                'actor_user_id' => $user->id,
                'subject_user_id' => $user->id,
                'event' => 'user.password_changed',
                'metadata' => [],
                'ip_address' => $request->ip(),
            ]);
        });

        Auth::guard('web')->logoutOtherDevices($validated['password']);
        $request->session()->regenerate();

        return back()->with('status', 'Password changed.');
    }
}
