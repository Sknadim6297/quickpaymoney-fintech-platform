@extends('layouts.app')

@section('title', 'Quick PayMoney | Profile & Security')

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

        <main class="content-area profile-area">
            <h1 class="portal-title">Profile &amp; Security</h1>
            <p class="portal-muted">Manage your account information and password.</p>

            <section class="portal-card profile-card">
                <h2>Account information</h2>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Account status:</strong> <span class="portal-badge {{ $user->account_status }}">{{ ucfirst($user->account_status) }}</span></p>
                <p><strong>Identity verification:</strong> <span class="portal-badge {{ $user->verification_status }}">{{ ucfirst($user->verification_status) }}</span></p>
            </section>

            <section class="portal-card profile-card">
                <h2>Personal details</h2>
                <form class="portal-form" method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <label for="name">Full name</label>
                    <input class="portal-input" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="120" autocomplete="name" required>
                    <label for="mobile">Mobile</label>
                    <input class="portal-input" id="mobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="20" autocomplete="tel">
                    <label for="gender">Gender</label>
                    <select class="portal-input" id="gender" name="gender">
                        <option value="">Prefer not to say</option>
                        @foreach (['Male', 'Female', 'Other'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender', $user->gender) === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                    <button class="portal-button" type="submit">Save profile</button>
                </form>
            </section>

            <section class="portal-card profile-card">
                <h2>Change password</h2>
                <form class="portal-form" method="POST" action="{{ route('password.change') }}">
                    @csrf
                    @method('PUT')
                    <label for="current_password">Current password</label>
                    <input class="portal-input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    <label for="password">New password</label>
                    <input class="portal-input" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
                    <label for="password_confirmation">Confirm new password</label>
                    <input class="portal-input" type="password" id="password_confirmation" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                    <button class="portal-button" type="submit">Change password</button>
                </form>
            </section>
        </main>

        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection
