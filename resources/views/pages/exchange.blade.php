@extends('layouts.app')

@section('title', 'Quick PayMoney | Exchange')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
@endsection

@section('styles')
<link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/exchange.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="page-wrapper">


        @include('partials.home-header')

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
                    Your Trusted USDT Exchange Platform
                </div>


                <div class="hero-features">

                    <div class="hero-feature">

                        <div>
                            <i class="bi bi-shield-fill"></i>

                            <strong>100% Safe</strong>
                        </div>

                        <small>
                            Your Funds, Our Priority
                        </small>

                    </div>


                    <div class="hero-feature">

                        <div>
                            <i class="bi bi-lightning-fill"></i>

                            <strong>Fast Transactions</strong>
                        </div>

                        <small>
                            Within Minutes
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

                <small>
                    BALANCE
                </small>

                @if (auth('web')->user()?->role === 'user')
                    <strong>{{ \App\Support\Money::formatUsd($recordedBalance) }}</strong>
                    <small class="balance-disclaimer">Not a wallet</small>
                @else
                    <strong>Sign in to view</strong>
                @endif

            </div>

        </section>



        <!-- =====================================
             ACTIONS
        ====================================== -->

        <section class="action-grid">

            <div class="row g-0">


                <!-- DEPOSIT -->

                <div class="col-6 col-md-3">

                    <a class="action-card" href="{{ route('deposit.create') }}" aria-label="Deposit add funds">

                        <div class="action-icon">

                            <i class="bi bi-wallet-fill"></i>

                        </div>

                        <h3 class="action-title">
                            Deposit
                        </h3>

                        <div class="action-subtitle">
                            Add Funds
                        </div>

                        <i class="bi bi-chevron-right action-arrow"></i>
                    </a>

                </div>


                <!-- SELL -->

                <div class="col-6 col-md-3">

                    <button class="action-card" type="button" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-arrow-left-right"></i>

                        </div>

                        <h3 class="action-title">
                            Sell
                        </h3>

                        <div class="action-subtitle">
                            Sell USDT
                        </div>

                        <i class="bi bi-chevron-right action-arrow"></i>
                    </button>

                </div>


                <!-- WITHDRAW -->

                <div class="col-6 col-md-3">

                    <button class="action-card" type="button" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-wallet2"></i>

                        </div>

                        <h3 class="action-title">
                            Withdraw
                        </h3>

                        <div class="action-subtitle">
                            Get Your Funds
                        </div>

                        <i class="bi bi-chevron-right action-arrow"></i>
                    </button>

                </div>


                <!-- REFERRAL -->

                <div class="col-6 col-md-3">

                    <button class="action-card" type="button" aria-disabled="true">

                        <div class="action-icon">

                            <i class="bi bi-people-fill"></i>

                        </div>

                        <h3 class="action-title">
                            Referral
                        </h3>

                        <div class="action-subtitle">
                            Earn Together
                        </div>

                        <i class="bi bi-chevron-right action-arrow"></i>
                    </button>

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

            Live Rates

            <span class="live-dot"></span>

        </div>

    </div>


    @forelse ($ratePlans as $plan)
        <article class="rate-card">
            <div class="rate-info">
                <div class="rate-icon {{ $plan->plan_key === 'base' ? 'usdt-icon' : '' }} {{ $plan->plan_key === 'prime' ? 'prime-icon' : '' }} {{ $plan->plan_key === 'vip' ? 'vip-icon' : '' }}">
                    <i class="bi {{ $plan->icon }}" aria-hidden="true"></i>
                </div>
                <div class="rate-details">
                    <div class="rate-name">
                        {{ $plan->name }}
                        @if ($plan->label)
                            <span class="rate-tag">{{ $plan->label }}</span>
                        @endif
                        @if ($plan->formattedMinimumAmount() !== '0')
                            <span class="rate-tag">Above ${{ $plan->formattedMinimumAmount() }}</span>
                        @endif
                    </div>
                    <small>1 USDT = {{ $plan->formattedRate() }} INR</small>
                    @if ($plan->description)
                        <small class="rate-description">{{ $plan->description }}</small>
                    @endif
                </div>
            </div>
            <div class="rate-value">
                <div class="rate-number">
                    ₹{{ $plan->formattedRate() }}
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </div>
                <small>1 USDT = {{ $plan->formattedRate() }} INR</small>
            </div>
        </article>
    @empty
        <article class="rate-card"><div class="rate-info"><div class="rate-details"><div class="rate-name">Exchange rates are temporarily unavailable.</div></div></div></article>
    @endforelse

    <section class="trade-usdt-banner">
                  <div class="trade-content">

            <h2>
                Trade <span>USDT</span>
            </h2>

            <div class="trade-points">
                <span>Secure</span>
                <b>•</b>
                <span>Fast</span>
                <b>•</b>
                <span>Reliable</span>
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
