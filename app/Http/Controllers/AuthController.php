<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        return redirect()->intended(route('home'))->with('status', 'Signed in successfully.');
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
        $tab = in_array($request->query('tab'), ['overview', 'history', 'security'], true)
            ? $request->query('tab')
            : 'overview';
        $deposits = collect();

        if ($tab === 'history') {
            $filters = $request->validate([
                'search' => ['nullable', 'string', 'max:150'],
                'status' => ['nullable', Rule::in(Deposit::STATUSES)],
            ]);

            $deposits = $user->deposits()
                ->when($filters['search'] ?? null, function ($query, string $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('deposit_id', 'like', '%'.$search.'%')
                            ->orWhere('transaction_reference', 'like', '%'.$search.'%');
                    });
                })
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->latest('submitted_at')
                ->paginate(10)
                ->withQueryString();
        }

        return view('pages.profile', compact('user', 'tab', 'deposits'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $section = $request->validate([
            'section' => ['required', Rule::in(['personal', 'bank', 'wallet'])],
        ])['section'];
        $user = $request->user('web');

        if ($section === 'personal') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'mobile' => ['nullable', 'string', 'max:20'],
                'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            ]);
            $event = 'user.profile_updated';
            $message = 'Profile details updated.';
        } elseif ($section === 'bank') {
            $validator = Validator::make($request->all(), [
                'bank_current_password' => ['required', 'current_password:web'],
                'account_holder_name' => ['required', 'string', 'max:120'],
                'bank_name' => ['required', 'string', 'max:120'],
                'account_number' => ['required', 'string', 'regex:/^\d{6,20}$/'],
                'ifsc_code' => ['required', 'string', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
                'branch_name' => ['required', 'string', 'max:120'],
                'account_type' => ['required', Rule::in(['Savings', 'Current'])],
            ]);
            if ($validator->fails()) {
                return redirect()->route('profile')
                    ->withErrors($validator)
                    ->withInput($request->except([
                        'bank_current_password',
                        'account_holder_name',
                        'bank_name',
                        'account_number',
                        'ifsc_code',
                        'branch_name',
                        'account_type',
                        'usdt_wallet_address',
                        'wallet_current_password',
                    ]));
            }
            $validated = $validator->validated();
            unset($validated['bank_current_password']);
            $event = 'user.bank_details_updated';
            $message = 'Bank details updated.';
        } else {
            $validator = Validator::make($request->all(), [
                'wallet_current_password' => ['required', 'current_password:web'],
                'usdt_wallet_address' => ['required', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                return redirect()->route('profile')
                    ->withErrors($validator)
                    ->withInput($request->except([
                        'bank_current_password',
                        'account_holder_name',
                        'bank_name',
                        'account_number',
                        'ifsc_code',
                        'branch_name',
                        'account_type',
                        'usdt_wallet_address',
                        'wallet_current_password',
                    ]));
            }
            $validated = $validator->validated();
            unset($validated['wallet_current_password']);
            $event = 'user.wallet_details_updated';
            $message = 'USDT wallet details updated.';
        }

        DB::transaction(function () use ($request, $validated, $user, $event): void {
            $user->fill($validated)->save();
            AuditLog::create([
                'actor_user_id' => $user->id,
                'subject_user_id' => $user->id,
                'event' => $event,
                'metadata' => ['updated_fields' => array_keys($validated)],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('profile')->with('status', $message);
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
