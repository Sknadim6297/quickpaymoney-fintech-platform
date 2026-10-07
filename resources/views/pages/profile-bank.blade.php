@extends('layouts.app')

@section('title', 'Quick PayMoney | Bank Details')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endsection

@section('styles')
    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/portal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/profile-dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">
        @include('partials.home-header')
        <main class="content-area profile-dashboard profile-subpage">
            @include('partials.profile-hero', ['user' => $user])
            <a class="profile-back-link" href="{{ route('profile') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Profile</a>
            <section class="profile-content-card">
                <div class="profile-content-heading"><span class="profile-content-icon"><i class="bi bi-bank" aria-hidden="true"></i></span><div><h2>Bank Details</h2><p>Manage your payout bank information. Changes require your current password.</p></div></div>
                @if ($errors->any())
                    <div class="portal-error" role="alert">Please check the highlighted fields and try again.</div>
                @endif
                @if ($user->account_number)
                    <p class="profile-private-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> Bank account on file ending in {{ substr($user->account_number, -4) }}. For your security, enter the full account number when saving changes.</p>
                @endif
                <form class="profile-form" method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="bank">
                    <div class="profile-form-grid">
                        <div class="profile-input-group"><label for="account-holder-name">Account Holder Name</label><input class="profile-form-control" id="account-holder-name" name="account_holder_name" value="{{ old('account_holder_name', $user->account_holder_name) }}" maxlength="120" autocomplete="off" required>@error('account_holder_name')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group"><label for="bank-name">Bank Name</label><input class="profile-form-control" id="bank-name" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}" maxlength="120" autocomplete="off" required>@error('bank_name')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group"><label for="account-number">Account Number</label><input class="profile-form-control" id="account-number" name="account_number" type="password" inputmode="numeric" minlength="6" maxlength="20" autocomplete="new-password" required>@error('account_number')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group"><label for="ifsc-code">IFSC Code</label><input class="profile-form-control profile-ifsc-input" id="ifsc-code" name="ifsc_code" value="{{ old('ifsc_code', $user->ifsc_code) }}" minlength="11" maxlength="11" autocapitalize="characters" autocomplete="off" required>@error('ifsc_code')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group"><label for="branch-name">Branch Name</label><input class="profile-form-control" id="branch-name" name="branch_name" value="{{ old('branch_name', $user->branch_name) }}" maxlength="120" autocomplete="off" required>@error('branch_name')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group"><label for="account-type">Account Type</label><select class="profile-form-control" id="account-type" name="account_type" required><option value="">Select account type</option>@foreach (['Savings', 'Current'] as $accountType)<option value="{{ $accountType }}" @selected(old('account_type', $user->account_type) === $accountType)>{{ $accountType }}</option>@endforeach</select>@error('account_type')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group profile-full-width"><label for="bank-current-password">Current Password</label><input class="profile-form-control" id="bank-current-password" type="password" name="bank_current_password" autocomplete="current-password" required>@error('bank_current_password')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                    </div>
                    <div class="profile-form-actions"><button class="profile-save-button" type="submit"><i class="bi bi-check-circle" aria-hidden="true"></i> Save Bank Details</button></div>
                </form>
            </section>
            <section class="profile-content-card profile-secondary-card">
                <div class="profile-content-heading"><span class="profile-content-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span><div><h2>USDT Wallet Details</h2><p>Update the wallet address on your customer profile. Re-authentication is required.</p></div></div>
                @if ($user->usdt_wallet_address)
                    <p class="profile-private-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> A wallet address is saved. Re-enter it to replace the saved address.</p>
                @endif
                <form class="profile-form" method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="wallet">
                    <div class="profile-form-grid">
                        <div class="profile-input-group profile-full-width"><label for="usdt-wallet-address">USDT Wallet Address</label><input class="profile-form-control" id="usdt-wallet-address" name="usdt_wallet_address" value="{{ old('usdt_wallet_address') }}" maxlength="255" autocomplete="off" required>@error('usdt_wallet_address')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                        <div class="profile-input-group profile-full-width"><label for="wallet-current-password">Current Password</label><input class="profile-form-control" id="wallet-current-password" type="password" name="wallet_current_password" autocomplete="current-password" required>@error('wallet_current_password')<span class="profile-field-error">{{ $message }}</span>@enderror</div>
                    </div>
                    <div class="profile-form-actions"><button class="profile-save-button" type="submit"><i class="bi bi-check-circle" aria-hidden="true"></i> Save Wallet Address</button></div>
                </form>
            </section>
        </main>
        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection

@section('scripts')
    @include('partials.profile-copy-script')
@endsection
