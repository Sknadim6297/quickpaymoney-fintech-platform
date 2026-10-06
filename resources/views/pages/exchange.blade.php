@extends('layouts.app')

@section('title', 'Quick PayMoney | Exchange')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
@endsection

@section('styles')
<link href="{{ asset('assets/css/exchange.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="page-wrapper">


        <!-- =====================================
         HEADER
    ====================================== -->

        <header class="top-header">

            <div class="header-inner">


                <a href="{{ route('home') }}"
                    class="brand">

                    <div class="brand-symbol">
                        <span>Q</span>
                    </div>

                    <div>

                        <div class="brand-text">
                            Quick Pay<span>Money</span>
                        </div>

                        <span class="brand-tagline">FAST · SAFE · GLOBAL
                        </span>

                    </div>

                </a>


                <div class="header-actions">

                    <a href="{{ route('login') }}"
                        class="header-btn">

                        <i class="bi bi-person-fill"></i>

                        Login

                    </a>


                    <a href="{{ route('contact') }}"
                        class="header-btn header-icon">

                        <i class="bi bi-headset"></i>

                    </a>

                </div>

            </div>

        </header>



        <!-- =====================================
         CONTENT
    ====================================== -->

        <main class="content">


        <!-- HERO -->

        <section class="hero-card">


            <div class="hero-content">

                <div class="hero-small">
                    WELCOME TO
                </div>

                <h1 class="hero-title">
                    Quick Pay<span>Money</span>
                </h1>

                <div class="hero-subtitle">
                    USDT-to-INR account portal
                </div>


                <div class="hero-features">

                    <div class="hero-feature">

                        <div>
                            <i class="bi bi-shield-fill"></i>

                            <strong>Security-focused account portal</strong>
                        </div>

                        <small>
                            No funds are held in this portal
                        </small>

                    </div>


                    <div class="hero-feature">

                        <div>
                            <i class="bi bi-lightning-fill"></i>

                            <strong>Settlement is not enabled</strong>
                        </div>

                        <small>
                            No transfers are connected
                        </small>

                    </div>


                    <div class="hero-feature">

                        <div>
                            <i class="bi bi-globe2"></i>

                            <strong>Global Access</strong>
                        </div>

                        <small>
                            Anytime, Anywhere
                        </small>

                    </div>

                </div>

            </div>


            <!-- COIN -->

            <div class="coin-area">

                <div class="coin-letter">
                    ₮
                </div>

            </div>


            <!-- BALANCE -->

            <div class="balance-box">

                <small>No wallet balance is tracked</small>

            </div>

        </section>



        <!-- =====================================
             ACTIONS
        ====================================== -->

        <section class="action-grid">

            <div class="row g-0">


                <!-- DEPOSIT -->

                <div class="col-6 col-md-3">

                    <div class="action-card" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-wallet-fill"></i>

                        </div>

                        <h3 class="action-title">
                            Deposit
                        </h3>

                        <div class="action-subtitle">
                            Not available yet
                        </div>

                    </div>

                </div>


                <!-- SELL -->

                <div class="col-6 col-md-3">

                    <div class="action-card" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-arrow-left-right"></i>

                        </div>

                        <h3 class="action-title">
                            Sell
                        </h3>

                        <div class="action-subtitle">
                            Not available yet
                        </div>

                    </div>

                </div>


                <!-- WITHDRAW -->

                <div class="col-6 col-md-3">

                    <div class="action-card" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-wallet2"></i>

                        </div>

                        <h3 class="action-title">
                            Withdraw
                        </h3>

                        <div class="action-subtitle">
                            Not available yet
                        </div>

                    </div>

                </div>


                <!-- REFERRAL -->

                <div class="col-6 col-md-3">

                    <div class="action-card" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-people-fill"></i>

                        </div>

                        <h3 class="action-title">
                            Referral
                        </h3>

                        <div class="action-subtitle">
                            Not available yet
                        </div>

                    </div>

                </div>

            </div>

        </section>



            <!-- =========================================
     EMoneyCHANGE RATES
========================================= -->

        <section class="rates-section">

    <!-- HEADER -->
    <div class="rates-header">

        <div class="rates-title">

            <i class="bi bi-bar-chart-fill"></i>

            <span>
                Exchange <b>Rates</b>
            </span>

        </div>

        <div class="live-badge">

            <i class="bi bi-lightning-fill"></i>

            {{ $exchangeRate ? 'Admin-configured reference rate' : 'Rates unavailable' }}

            <span class="live-dot"></span>

        </div>

    </div>
    <p class="portal-muted">Exchange requests are unavailable until approved exchange rates and business rules are configured. No funds are transferred or settled.</p>


    <!-- BASE RATE -->
    <div class="rate-card">

        <div class="rate-info">

            <div class="rate-icon usdt-icon">
                ₮
            </div>

            <div class="rate-details">

                <div class="rate-name">

                    Base Rate

                    <span class="rate-tag">{{ $exchangeRate ? 'ADMIN MANAGED' : 'NOT CONFIGURED' }}</span>

                </div>

                <small>
                    @if ($exchangeRate) 1 USDT = ₹{{ $exchangeRate->rate }} INR @else Exchange rate unavailable @endif
                </small>

            </div>

        </div>


        <div class="rate-value">

            <div class="rate-number">
                {{ $exchangeRate ? '₹'.$exchangeRate->rate : '—' }}
                <i class="bi bi-chevron-right"></i>
            </div>

            <small>
                {{ $exchangeRate ? 'Reference rate · no settlement available' : 'No approved rate is configured' }}
            </small>

        </div>

    </div>


    <!-- PRIME RATE -->
    <div class="rate-card">

        <div class="rate-info">

            <div class="rate-icon prime-icon">

                <i class="bi bi-crown-fill"></i>

            </div>

            <div class="rate-details">

                <div class="rate-name">

                    Prime Rate

                    <span class="rate-tag">
                        Rate not configured
                    </span>

                </div>

                <small>
                    Exchange rate unavailable
                </small>

            </div>

        </div>


        <div class="rate-value">

            <div class="rate-number">

                —

                <i class="bi bi-chevron-right"></i>

            </div>

            <small>
                No approved rate is configured
            </small>

        </div>

    </div>


    <!-- VIP RATE -->
    <div class="rate-card">

        <div class="rate-info">

            <div class="rate-icon vip-icon">

                <i class="bi bi-gem"></i>

            </div>

            <div class="rate-details">

                <div class="rate-name">

                    VIP Rate

                    <span class="rate-tag">
                        Rate not configured
                    </span>

                </div>

                <small>
                    Exchange rate unavailable
                </small>

            </div>

        </div>


        <div class="rate-value">

            <div class="rate-number">

                —

                <i class="bi bi-chevron-right"></i>

            </div>

            <small>
                No approved rate is configured
            </small>

        </div>

    </div>

    <section class="trade-usdt-banner">
                  <div class="trade-content">

            <h2>
                Exchange service <span>unavailable</span>
            </h2>

            <div class="trade-points">
                <span>Rates unavailable</span>
                <b>•</b>
                <span>No request creation</span>
                <b>•</b>
                <span>No settlement</span>
            </div>

        </div>

    </section>
    <!-- CENTER USDT CARD -->

    


    <!-- RIGHT -->

    <div class="trade-right">

        <h3>
            Global Access
        </h3>

        <span>
            Anytime, Anywhere
        </span>

    </div>
</section>
    </main>



        <!-- =====================================
         WHATSAPP
    ====================================== -->

        <a href="{{ route('contact') }}" aria-label="Contact support"
            class="whatsapp">

            <i class="bi bi-headset"></i>

        </a>



        <!-- =====================================
         BOTTOM NAV
    ====================================== -->

        @include('partials.bottom-nav', ['active' => 'exchange', 'variant' => 'exchange'])

    </div>
@endsection
