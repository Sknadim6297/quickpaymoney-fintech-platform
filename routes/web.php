<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminDepositController;
use App\Http\Controllers\AdminDepositSettingsController;
use App\Http\Controllers\AdminExchangeController;
use App\Http\Controllers\AdminRateController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\PublicPagesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPagesController::class, 'home'])->name('home');
Route::get('/exchange', [PublicPagesController::class, 'exchange'])->name('exchange');
Route::view('/contact', 'pages.contact')->name('contact');
Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');

Route::middleware('guest:web')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', fn () => view('pages.register'))->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware(['auth:web', 'auth.session'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('active.account')->group(function (): void {
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::put('/security/password', [AuthController::class, 'changePassword'])->name('password.change');
        Route::get('/deposit', [DepositController::class, 'create'])->name('deposit.create');
        Route::post('/deposit', [DepositController::class, 'store'])->middleware('throttle:5,1')->name('deposit.store');
        Route::get('/deposit/payment-qr', [DepositController::class, 'paymentQr'])->name('deposit.payment-qr');
        Route::get('/deposits/{deposit}', [DepositController::class, 'show'])->name('deposits.show');
        Route::get('/deposits/{deposit}/proof', [DepositController::class, 'proof'])->name('deposits.proof');
    });
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
        Route::get('/forgot-password', [AdminAuthController::class, 'showForgotPassword'])->name('password.request');
        Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/reset-password/{token}', [AdminAuthController::class, 'showResetPassword'])->name('password.reset');
        Route::post('/reset-password', [AdminAuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
    });

    Route::middleware(['auth:admin', 'admin.session', 'admin.role'])->group(function (): void {
        Route::get('/two-factor/setup', [AdminAuthController::class, 'showSetup'])->name('2fa.setup');
        Route::post('/two-factor/setup', [AdminAuthController::class, 'enableTwoFactor'])->middleware('throttle:6,1')->name('2fa.enable');
        Route::get('/two-factor/challenge', [AdminAuthController::class, 'showChallenge'])->name('2fa.challenge');
        Route::post('/two-factor/challenge', [AdminAuthController::class, 'verifyTwoFactor'])->middleware('throttle:6,1')->name('2fa.verify');

        Route::middleware('admin.2fa')->group(function (): void {
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
            Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
            Route::get('/', AdminDashboardController::class);
            Route::get('/profile', [AdminAuthController::class, 'profile'])->name('profile');
            Route::put('/profile', [AdminAuthController::class, 'updateProfile'])->name('profile.update');
            Route::put('/security/password', [AdminAuthController::class, 'changePassword'])->name('password.change');

            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

            Route::get('/exchanges', [AdminExchangeController::class, 'index'])->name('exchanges.index');
            Route::get('/exchanges/{exchangeRequest}', [AdminExchangeController::class, 'show'])->name('exchanges.show');
            Route::put('/exchanges/{exchangeRequest}', [AdminExchangeController::class, 'update'])->name('exchanges.update');

            Route::get('/rates', [AdminRateController::class, 'show'])->name('rates.edit');
            Route::post('/rates', [AdminRateController::class, 'store'])->name('rates.store');
            Route::put('/rates', [AdminRateController::class, 'update'])->name('rates.update');
            Route::put('/rates/{exchangeRate}', [AdminRateController::class, 'updatePlan'])->name('rates.plans.update');
            Route::patch('/rates/{exchangeRate}/status', [AdminRateController::class, 'toggleStatus'])->name('rates.plans.status');
            Route::delete('/rates/{exchangeRate}', [AdminRateController::class, 'destroy'])->name('rates.plans.destroy');

            Route::get('/deposit-settings', [AdminDepositSettingsController::class, 'edit'])->name('deposit-settings.edit');
            Route::put('/deposit-settings', [AdminDepositSettingsController::class, 'update'])->name('deposit-settings.update');
            Route::delete('/deposit-settings', [AdminDepositSettingsController::class, 'destroy'])->name('deposit-settings.destroy');
            Route::get('/deposit-settings/qr', [AdminDepositSettingsController::class, 'qr'])->name('deposit-settings.qr');
            Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
            Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('deposits.show');
            Route::put('/deposits/{deposit}', [AdminDepositController::class, 'update'])->name('deposits.update');
            Route::get('/deposits/{deposit}/proof', [AdminDepositController::class, 'proof'])->name('deposits.proof');
        });
    });
});
