@extends('layouts.app')

@section('title', 'Quick PayMoney | Home')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet">
@endsection

@section('styles')
<link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">

        @include('partials.home-header')


        <!-- CONTENT -->
        <main class="content-area">

        <!-- LIVE RATE -->
        <section class="rate-card">

            <div class="live-title">
                <span class="live-dot"></span>
                {{ $exchangeRate ? 'CONFIGURED RATE' : 'RATE UNAVAILABLE' }}
            </div>

            <div class="rate-title">
                @if ($exchangeRate)
                    1 USDT = <span><i class="bi bi-currency-rupee"></i>{{ $exchangeRate->rate }}</span>
                @else
                    USDT ↔ INR <span>Not configured</span>
                @endif
            </div>

            <div class="rate-subtitle">
                Quick PayMoney account portal
                <br>
                Exchange service unavailable
            </div>


            <!-- COINS -->

            <div class="coin-area">

                <div class="coin usdt-coin">
                    ₮
                </div>

                <div class="exchange-arrow">
                    ⇄
                </div>

                <div class="coin inr-coin">
                    <i class="bi bi-currency-rupee"></i>
                </div>

            </div>


            <!-- BUTTON -->

            <button class="exchange-btn" type="button" disabled aria-disabled="true">
                Exchange unavailable
                <i class="bi bi-arrow-down"></i>
            </button>

        </section>


        <!-- PLATFORM STATS -->

        <section class="stats-card">

            <div class="section-title">

                <span class="title-line"></span>

                <span class="green-dot"></span>

                PLATFORM INFORMATION

                <span class="title-line"></span>

            </div>


            <div class="stats-inner">

                <div class="row g-0">

                    <!-- CLIENTS -->

                    <div class="col-4 stat-item">

                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>

                        <div class="stat-label">
                            ACTIVE ACCOUNTS<br>
                            NOT AVAILABLE
                        </div>

                        <div class="stat-value">
                            —
                        </div>

                    </div>


                    <!-- DEPOSIT -->

                    <div class="col-4 stat-item">

                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>

                        <div class="stat-label">
                            DEPOSITS<br>
                            NOT TRACKED
                        </div>

                        <div class="stat-value">
                            —
                        </div>

                    </div>


                    <!-- INR -->

                    <div class="col-4 stat-item inr">

                        <div class="stat-icon">
                            <i class="bi bi-currency-rupee"></i>
                        </div>

                        <div class="stat-label">
                            SETTLEMENTS<br>
                            NOT TRACKED
                        </div>

                        <div class="stat-value green">
                            —
                        </div>

                    </div>

                </div>


                <!-- EMoneyTRA DATA -->

                <!-- RECENT CONVERSIONS -->
<div class="conversion-card">

  

    <div class="table-responsive">

        <table class="conversion-table">

            <thead>
                <tr>
                    <th>CLIENT</th>
                    <th>DEPOSIT</th>
                    <th>CONVERTED</th>
                </tr>
            </thead>

            <tbody>
                <tr><td colspan="3" class="text-center">No exchange activity is available to display.</td></tr>
            </tbody>

        </table>

    </div>

</div>

            </div>

        </section>

            <!-- WHY CHOOSE US -->
        <section class="why-section">

            <div class="section-heading">

                <span class="heading-line"></span>

                <span class="green-dot"></span>

                WHY CHOOSE US

                <span class="heading-line"></span>

            </div>


            <div class="why-grid">

                <!-- Best Rate -->
                <div class="why-card">

                    <div class="why-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>

                    <div class="why-content">
                        <h4>Rates unavailable</h4>
                        <p>No approved rate is configured</p>
                    </div>

                </div>


                <!-- Fast Transaction -->
                <div class="why-card">

                    <div class="why-icon">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>

                    <div class="why-content">
                        <h4>Settlement disabled</h4>
                        <p>No payment integration is connected</p>
                    </div>

                </div>


                <!-- Secure -->
                <div class="why-card">

                    <div class="why-icon secure-icon">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>

                    <div class="why-content">
                        <h4>Account security</h4>
                        <p>Authentication and access controls</p>
                    </div>

                </div>


                <!-- No Lien Freeze -->
                <div class="why-card">

                    <div class="why-icon green-icon">
                        <i class="bi bi-unlock-fill"></i>
                    </div>

                    <div class="why-content">
                        <h4>No fund custody</h4>
                        <p>Wallets and transfers are not enabled</p>
                    </div>

                </div>

            </div>

        </section>


            <!-- TRUST & SUPPORT -->
        <section class="trust-section">

    <!-- Freeze-Free Transactions -->
    <div class="trust-card">

        <div class="trust-icon">
            <i class="bi bi-unlock-fill"></i>
        </div>

        <div class="trust-content">
            <h4>Transfers not enabled</h4>
            <p>
                No payments or settlements are performed by this portal.
            </p>
        </div>

    </div>


    <!-- Trusted Banking Network -->
    <div class="trust-card">

        <div class="trust-icon">
            <i class="bi bi-bank2"></i>
        </div>

        <div class="trust-content">
            <h4>No banking integration</h4>
            <p>
                Banking details and payment integrations are not configured.
            </p>
        </div>

    </div>


    <!-- 24/7 Customer Support -->
    <div class="trust-card">

        <div class="trust-icon support-icon">
            <i class="bi bi-headset"></i>
        </div>

        <div class="trust-content">
            <h4>24/7 Customer Support</h4>
            <p>
                Professional assistance whenever you need help.
            </p>
        </div>

    </div>

</section>

    </main>


        <!-- WHATSAPP -->

        <a href="{{ route('contact') }}" aria-label="Contact support"
            class="whatsapp">

            <i class="bi bi-headset"></i>

        </a>


        <!-- BOTTOM NAV -->

        @include('partials.bottom-nav', ['active' => 'home', 'variant' => 'standard'])

    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
@endsection
