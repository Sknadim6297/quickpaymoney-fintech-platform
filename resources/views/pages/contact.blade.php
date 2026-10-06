@extends('layouts.app')

@section('title', 'Quick PayMoney | Premium Support')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
@endsection

@section('styles')
<link href="{{ asset('assets/css/contact.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">


        <!-- HEADER -->

        @include('partials.account-header', ['actionRoute' => 'login', 'actionLabel' => 'Login', 'actionClass' => 'header-btn', 'showContact' => false])



        <!-- SUPPORT -->

        <main class="support-area">


        <!-- PREMIUM SUPPORT -->

        <div class="support-banner">

            <div class="support-icon">

                <i class="bi bi-headset"></i>

            </div>

            <h1>
                Quick PayMoney Support
            </h1>

            <p>
                Support channels are not configured yet.
            </p>

        </div>



        <!-- FORM CARD -->

        <div class="support-card">


            <div class="support-heading">

                <h2>
                    Let's Solve Your Problem
                </h2>

                <p>
                    Contact form submission is unavailable. No message will be sent or stored.
                </p>

            </div>



            <form action="{{ route('contact') }}"
                  method="post">
                <fieldset disabled style="border:0;padding:0;margin:0">


                <!-- NAME -->

                <div class="input-group-custom">

                    <input type="text"
                           name="name"
                           class="form-control-custom"
                           placeholder="Full Name"
                           required>

                </div>


                <!-- MOBILE -->

                <div class="input-group-custom">

                    <input type="tel"
                           name="mobile"
                           class="form-control-custom"
                           placeholder="Mobile Number"
                           maxlength="10"
                           inputmode="numeric"
                           pattern="[0-9]{10}"
                           required>

                </div>


                <!-- EMAIL -->

                <div class="input-group-custom">

                    <input type="email"
                           name="email"
                           class="form-control-custom"
                           placeholder="Email Address"
                           required>

                </div>


                <!-- SUBJECT -->

                <div class="input-group-custom">

                    <input type="text"
                           name="subject"
                           class="form-control-custom"
                           placeholder="Subject"
                           required>

                </div>


                <!-- GENDER -->

                <div class="gender-label">
                    Select Gender
                </div>


                <div class="gender-options">

                    <div class="gender-option">

                        <input type="radio"
                               name="gender"
                               id="male"
                               value="Male"
                               required>

                        <label for="male">
                            Male
                        </label>

                    </div>


                    <div class="gender-option">

                        <input type="radio"
                               name="gender"
                               id="female"
                               value="Female">

                        <label for="female">
                            Female
                        </label>

                    </div>


                    <div class="gender-option">

                        <input type="radio"
                               name="gender"
                               id="other"
                               value="Other">

                        <label for="other">
                            Other
                        </label>

                    </div>

                </div>


                <!-- MESSAGE -->

                <div class="input-group-custom">

                    <textarea name="message"
                              class="form-control-custom"
                              placeholder="Describe your problem..."
                              required></textarea>

                </div>


                <!-- SUBMIT -->

                <button type="submit"
                        class="submit-btn">

                    <i class="bi bi-send-fill"></i>

                    Submit Request

                </button>

                </fieldset>
            </form>


        </div> 

    </main>

        
        <!-- =====================================
         WHATSAPP
    ====================================== -->

        <!-- BOTTOM NAV -->

        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])

    </div>
@endsection
