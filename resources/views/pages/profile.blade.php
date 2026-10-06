@extends('layouts.app')

@section('title', 'Quick PayMoney | Profile')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
@endsection

@section('styles')
    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/portal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">
        @include('partials.home-header')

        <main class="content-area profile-area @if ($tab === 'overview') profile-area-overview @endif">
            <h1 class="portal-title">Profile</h1>
            <p class="portal-muted">Manage your account details and security settings.</p>

            <nav class="portal-tabs" aria-label="Profile sections">
                @foreach (['overview' => 'Overview', 'details' => 'Profile Details'] as $key => $label)
                    <a href="{{ route('profile', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>

            @if ($tab === 'overview')
                <section class="portal-card profile-card account-information-card">
                    <h2 class="profile-section-title"><i class="bi bi-person-vcard" aria-hidden="true"></i> Account Information</h2>
                    <div class="account-information-grid">
                        <article class="account-information-item">
                            <span class="account-information-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <div><span>Email Address</span><strong>{{ $user->email }}</strong></div>
                        </article>
                        <article class="account-information-item">
                            <span class="account-information-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
                            <div><span>Account Status</span><strong><span class="portal-badge {{ $user->account_status }}">{{ ucfirst($user->account_status) }}</span></strong></div>
                        </article>
                        <article class="account-information-item">
                            <span class="account-information-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                            <div><span>Identity Verification</span><strong><span class="portal-badge {{ $user->verification_status }}">{{ ucfirst($user->verification_status) }}</span></strong></div>
                        </article>
                        <article class="account-information-item account-balance-item">
                            <span class="account-information-icon"><i class="bi bi-currency-dollar" aria-hidden="true"></i></span>
                            <div><span>Internal Recorded USD Balance</span><strong>{{ \App\Support\Money::formatUsd($user->balance) }}</strong></div>
                        </article>
                    </div>
                    <p class="account-information-disclaimer">This is an internal recorded balance, not a custodial wallet or confirmation of USDT holdings.</p>
                    <a class="account-wallet-link" href="{{ route('wallet') }}"><i class="bi bi-wallet2" aria-hidden="true"></i> View My Wallet</a>
                </section>

                <section class="portal-card profile-card profile-security-card">
                    <h2 class="profile-section-title"><i class="bi bi-shield-lock" aria-hidden="true"></i> Security</h2>
                    <p class="profile-security-description">Update your password to keep your customer account secure.</p>
                    <form class="profile-security-form" method="POST" action="{{ route('password.change') }}">
                        @csrf
                        @method('PUT')
                        <div class="profile-security-fields">
                            <div>
                                <label for="current_password">Current password</label>
                                <input class="profile-form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                                @error('current_password')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label for="password">New password</label>
                                <input class="profile-form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
                                @error('password')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation">Confirm new password</label>
                                <input class="profile-form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div class="profile-form-actions">
                            <button class="profile-save-button" type="submit"><i class="bi bi-lock" aria-hidden="true"></i> Change password</button>
                        </div>
                    </form>
                </section>
            @else
                <section class="portal-card profile-card profile-details-card">
                    <h2 class="profile-section-title"><i class="bi bi-person-circle" aria-hidden="true"></i> Personal Information</h2>
                    <form class="profile-details-form" method="POST" action="{{ route('profile.update') }}" autocomplete="on">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="personal">
                        <div class="profile-form-grid">
                            <div class="profile-input-group">
                                <label for="profile-name">Full Name</label>
                                <input class="profile-form-control" id="profile-name" name="name" value="{{ old('name', $user->name) }}" maxlength="120" autocomplete="name" required>
                                @error('name')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="profile-mobile">Mobile Number</label>
                                <input class="profile-form-control" id="profile-mobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="20" autocomplete="tel">
                                @error('mobile')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="profile-email">Email Address</label>
                                <input class="profile-form-control" id="profile-email" type="email" value="{{ $user->email }}" autocomplete="email" readonly aria-describedby="profile-email-help">
                                <small class="profile-field-help" id="profile-email-help">Email changes are not available from this profile form.</small>
                            </div>
                            <div class="profile-input-group">
                                <label for="profile-gender">Gender</label>
                                <select class="profile-form-control" id="profile-gender" name="gender">
                                    <option value="">Prefer not to say</option>
                                    @foreach (['Male', 'Female', 'Other'] as $gender)
                                        <option value="{{ $gender }}" @selected(old('gender', $user->gender) === $gender)>{{ $gender }}</option>
                                    @endforeach
                                </select>
                                @error('gender')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="profile-form-actions"><button class="profile-save-button" type="submit"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Save Details</button></div>
                    </form>
                </section>

                <section class="portal-card profile-card profile-details-card">
                    <h2 class="profile-section-title"><i class="bi bi-bank" aria-hidden="true"></i> Bank Details</h2>
                    <p class="profile-security-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> Bank details are encrypted at rest. Confirm your password before saving changes.</p>
                    <form class="profile-details-form" method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="bank">
                        <div class="profile-form-grid">
                            <div class="profile-input-group">
                                <label for="account-holder-name">Account Holder Name</label>
                                <input class="profile-form-control" id="account-holder-name" name="account_holder_name" value="{{ old('account_holder_name', $user->account_holder_name) }}" maxlength="120" required>
                                @error('account_holder_name')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="bank-name">Bank Name</label>
                                <input class="profile-form-control" id="bank-name" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}" maxlength="120" required>
                                @error('bank_name')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="account-number">Account Number</label>
                                <input class="profile-form-control" id="account-number" name="account_number" type="password" value="{{ old('account_number', $user->account_number) }}" inputmode="numeric" minlength="6" maxlength="20" autocomplete="new-password" required>
                                @error('account_number')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="ifsc-code">IFSC Code</label>
                                <input class="profile-form-control profile-ifsc-input" id="ifsc-code" name="ifsc_code" value="{{ old('ifsc_code', $user->ifsc_code) }}" minlength="11" maxlength="11" autocapitalize="characters" required>
                                @error('ifsc_code')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="branch-name">Branch Name</label>
                                <input class="profile-form-control" id="branch-name" name="branch_name" value="{{ old('branch_name', $user->branch_name) }}" maxlength="120" required>
                                @error('branch_name')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="account-type">Account Type</label>
                                <select class="profile-form-control" id="account-type" name="account_type" required>
                                    <option value="">Select Account Type</option>
                                    @foreach (['Savings', 'Current'] as $accountType)
                                        <option value="{{ $accountType }}" @selected(old('account_type', $user->account_type) === $accountType)>{{ $accountType }} Account</option>
                                    @endforeach
                                </select>
                                @error('account_type')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group profile-reauth-field">
                                <label for="bank-current-password">Current Password</label>
                                <input class="profile-form-control" id="bank-current-password" type="password" name="bank_current_password" autocomplete="current-password" required>
                                @error('bank_current_password')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="profile-form-actions"><button class="profile-save-button" type="submit"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Save Details</button></div>
                    </form>
                </section>

                <section class="portal-card profile-card profile-details-card">
                    <h2 class="profile-section-title"><i class="bi bi-wallet2" aria-hidden="true"></i> USDT Wallet Details</h2>
                    <p class="profile-security-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> Confirm your password before changing your wallet address.</p>
                    <form class="profile-details-form" method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="wallet">
                        <div class="profile-wallet-grid">
                            <div class="profile-input-group">
                                <label for="usdt-wallet-address">USDT Wallet Address</label>
                                <input class="profile-form-control" id="usdt-wallet-address" name="usdt_wallet_address" value="{{ old('usdt_wallet_address', $user->usdt_wallet_address) }}" maxlength="255" required>
                                @error('usdt_wallet_address')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="profile-input-group">
                                <label for="wallet-current-password">Current Password</label>
                                <input class="profile-form-control" id="wallet-current-password" type="password" name="wallet_current_password" autocomplete="current-password" required>
                                @error('wallet_current_password')<span class="profile-field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="profile-form-actions"><button class="profile-save-button" type="submit"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Save Details</button></div>
                    </form>
                </section>
            @endif
        </main>

        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection
