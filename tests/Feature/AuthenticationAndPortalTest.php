<?php

namespace Tests\Feature;

use App\Models\ExchangeRequest;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class AuthenticationAndPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_logs_in_immediately_without_email_verification(): void
    {
        $response = $this->withSession(['url.intended' => route('dashboard')])->post(route('register.store'), [
            'name' => 'Alice Example',
            'email' => 'ALICE@example.test',
            'mobile' => '9876543210',
            'password' => 'StrongPassword!123',
            'password_confirmation' => 'StrongPassword!123',
            'terms' => '1',
        ]);

        $user = User::where('email', 'alice@example.test')->firstOrFail();
        $response->assertRedirect(route('home'));
        $this->assertSame('user', $user->role);
        $this->assertSame('active', $user->account_status);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('StrongPassword!123', $user->password));
        $this->assertAuthenticatedAs($user, 'web');
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Your account has been created successfully.');
        $this->get(route('dashboard'))->assertRedirect(route('home'));
        $this->assertFalse(Route::has('verification.notice'));
    }

    public function test_user_login_redirects_home_and_suspended_account_is_denied(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()])->fresh();
        $this->withSession(['url.intended' => route('dashboard')])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user, 'web');
        $this->get(route('login'))->assertRedirect(route('home'));

        Auth::guard('web')->logout();
        $user->forceFill(['account_status' => 'suspended'])->save();
        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_dashboard_redirects_home_and_profile_is_a_separate_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()])->fresh();
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        ExchangeRequest::create([
            'user_id' => $otherUser->id,
            'usdt_amount' => '98765.43210000',
            'exchange_rate' => '91.00000000',
            'inr_amount' => '8987654.32',
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'web')->get(route('dashboard'))
            ->assertRedirect(route('home'));

        $this->actingAs($user, 'web')->get(route('profile'))
            ->assertOk()
            ->assertSee('Profile & Security')
            ->assertSee(route('profile', ['tab' => 'history']))
            ->assertSee(route('profile', ['tab' => 'security']))
            ->assertSee('Save Details')
            ->assertDontSee('Change password')
            ->assertDontSee('Account overview')
            ->assertDontSee('Exchange and transaction history')
            ->assertDontSee('98765.43210000');

        $this->actingAs($user, 'web')->get(route('profile', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Change password');

        $unverified = User::factory()->unverified()->create()->fresh();
        $this->actingAs($unverified, 'web')->get(route('profile'))
            ->assertOk();
    }

    public function test_profile_details_and_password_can_be_updated_from_profile_page(): void
    {
        $user = User::factory()->create(['password' => 'CurrentStrong!Password123']);
        $otherUser = User::factory()->create(['name' => 'Unchanged User']);

        $this->actingAs($user, 'web')
            ->from(route('profile'))
            ->put(route('profile.update'), [
                'section' => 'personal',
                'name' => 'Updated Profile Name',
                'mobile' => '9876543210',
                'gender' => 'Other',
                'email' => 'changed@example.test',
                'user_id' => $otherUser->id,
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'Profile details updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Profile Name',
            'mobile' => '9876543210',
            'gender' => 'Other',
            'email' => $user->email,
        ]);
        $this->assertSame('Unchanged User', $otherUser->fresh()->name);

        $this->from(route('profile'))->put(route('password.change'), [
            'current_password' => 'CurrentStrong!Password123',
            'password' => 'ReplacementStrong!Password456',
            'password_confirmation' => 'ReplacementStrong!Password456',
        ])->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'Password changed.');

        $this->assertTrue(Hash::check('ReplacementStrong!Password456', $user->fresh()->password));
    }

    public function test_profile_bank_and_wallet_details_require_reauthentication_and_are_encrypted(): void
    {
        $user = User::factory()->create(['password' => 'CurrentStrong!Password123']);

        $this->actingAs($user, 'web')
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Personal Information')
            ->assertSee('Bank Details')
            ->assertSee('USDT Wallet Details')
            ->assertSee('Email Address')
            ->assertSee('name="account_number"', false)
            ->assertSee('name="usdt_wallet_address"', false);

        $bankDetails = [
            'section' => 'bank',
            'bank_current_password' => 'wrong-password',
            'account_holder_name' => 'Morgan Example',
            'bank_name' => 'Example Bank',
            'account_number' => '123456789012',
            'ifsc_code' => 'ABCD0123456',
            'branch_name' => 'Central Branch',
            'account_type' => 'Savings',
        ];

        $this->from(route('profile'))
            ->put(route('profile.update'), $bankDetails)
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('bank_current_password')
            ->assertSessionMissing('_old_input.account_number')
            ->assertSessionMissing('_old_input.bank_current_password');
        $this->assertNull($user->fresh()->account_number);

        $this->from(route('profile'))
            ->put(route('profile.update'), [
                ...$bankDetails,
                'bank_current_password' => 'CurrentStrong!Password123',
                'ifsc_code' => 'invalid',
                'account_type' => 'Business',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors(['ifsc_code', 'account_type'])
            ->assertSessionMissing('_old_input.account_number');
        $this->assertNull($user->fresh()->account_number);

        $this->from(route('profile'))
            ->put(route('profile.update'), [
                ...$bankDetails,
                'bank_current_password' => 'CurrentStrong!Password123',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'Bank details updated.');

        $user->refresh();
        $this->assertSame('Morgan Example', $user->account_holder_name);
        $this->assertSame('Example Bank', $user->bank_name);
        $this->assertSame('123456789012', $user->account_number);
        $this->assertSame('ABCD0123456', $user->ifsc_code);
        $this->assertSame('Central Branch', $user->branch_name);
        $this->assertSame('Savings', $user->account_type);
        $this->assertStringNotContainsString('123456789012', $user->getRawOriginal('account_number'));
        $this->assertDatabaseHas('audit_logs', [
            'subject_user_id' => $user->id,
            'event' => 'user.bank_details_updated',
        ]);
        $this->assertStringNotContainsString(
            '123456789012',
            json_encode(\App\Models\AuditLog::where('event', 'user.bank_details_updated')->firstOrFail()->metadata)
        );

        $walletDetails = [
            'section' => 'wallet',
            'wallet_current_password' => 'CurrentStrong!Password123',
            'usdt_wallet_address' => 'TQn9Y2khDD95J42FQtQTdwV',
        ];

        $this->from(route('profile'))
            ->put(route('profile.update'), $walletDetails)
            ->assertRedirect(route('profile'))
            ->assertSessionHas('status', 'USDT wallet details updated.');

        $user->refresh();
        $this->assertSame($walletDetails['usdt_wallet_address'], $user->usdt_wallet_address);
        $this->assertStringNotContainsString($walletDetails['usdt_wallet_address'], $user->getRawOriginal('usdt_wallet_address'));

        $this->get(route('profile'))
            ->assertOk()
            ->assertSee('Morgan Example')
            ->assertSee('Example Bank')
            ->assertSee('TQn9Y2khDD95J42FQtQTdwV');
    }

    public function test_home_header_has_guest_and_authenticated_account_controls(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertDontSee(route('register'))
            ->assertDontSee('data-user-menu-toggle');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('register'));

        $user = User::factory()->create(['name' => 'Morgan Example']);
        $this->actingAs($user, 'web')->get(route('home'))
            ->assertOk()
            ->assertSee('Account menu for Morgan Example')
            ->assertSee('>Profile</a>', false)
            ->assertSee('>History</a>', false)
            ->assertSee(route('profile'))
            ->assertSee(route('profile', ['tab' => 'history']))
            ->assertSee(route('logout'))
            ->assertSee('name="_token"', false)
            ->assertSee('data-user-menu-toggle');
    }

    public function test_homepage_restores_original_demo_rate_stats_and_conversion_rows(): void
    {
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('LIVE RATE')
            ->assertSee('1 USDT =')
            ->assertSee('>100</span>', false)
            ->assertSee('Fast &amp; secure USDT', false)
            ->assertSee('Exchange Now')
            ->assertSee('LIVE PLATFORM STATS')
            ->assertSee('500')
            ->assertSee('$2.5M')
            ->assertSee('27.5Cr')
            ->assertSee('+91 96****3461')
            ->assertSee('$4,187')
            ->assertSee('4,60,570')
            ->assertSee('Best Rate')
            ->assertSee('Freeze-Free Transactions')
            ->assertSee('Trusted Banking Network');

        $this->assertStringNotContainsString('No exchange activity is available', $response->getContent());
        $this->assertStringNotContainsString('Exchange service unavailable', $response->getContent());
    }

    public function test_homepage_formats_live_rate_for_display_without_changing_stored_precision(): void
    {
        $rate = ExchangeRate::where('plan_key', 'base')->firstOrFail();
        $rate->update(['rate' => '100.00000000']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('>100</span>', false)
            ->assertSee('family=Poppins:wght@400;500;600;700');

        $this->assertSame(100.0, (float) $rate->fresh()->rate);

        $rate->update(['rate' => '100.50000000']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('>100.5</span>', false);

        $this->assertSame(100.5, (float) $rate->fresh()->rate);
    }

    public function test_exchange_page_restores_original_demo_rates_and_inert_action_cards(): void
    {
        $response = $this->get(route('exchange'))
            ->assertOk()
            ->assertSee('Your Trusted USDT Exchange Platform')
            ->assertSee('100% Safe')
            ->assertSee('Fast Transactions')
            ->assertSee('Global Access')
            ->assertSee('BALANCE')
            ->assertSee('Sign in to view')
            ->assertSee('Add Funds')
            ->assertSee('Sell USDT')
            ->assertSee('Get Your Funds')
            ->assertSee('Earn Together')
            ->assertSee('Live Rates')
            ->assertSee('₹100')
            ->assertSee('Above $10,000')
            ->assertSee('₹115')
            ->assertSee('Above $20,000')
            ->assertSee('₹120')
            ->assertSee('Trade ')
            ->assertSee('Reliable');

        $this->assertSame(3, substr_count($response->getContent(), 'aria-disabled="true"'));
        $this->assertStringContainsString('href="' . route('deposit.create') . '"', $response->getContent());
        $this->assertStringNotContainsString('Not available yet', $response->getContent());
        $this->assertStringNotContainsString('No settlement available', $response->getContent());
        $this->assertStringNotContainsString('Rates unavailable', $response->getContent());
    }

    public function test_exchange_page_formats_every_rate_card_and_uses_visible_prime_icon(): void
    {
        $rate = ExchangeRate::where('plan_key', 'base')->firstOrFail();
        $rate->update(['rate' => '100.00000000']);

        $response = $this->get(route('exchange'))
            ->assertOk()
            ->assertSee('1 USDT = 100 INR')
            ->assertSee('₹100')
            ->assertSee('1 USDT = 115 INR')
            ->assertSee('₹115')
            ->assertSee('1 USDT = 120 INR')
            ->assertSee('₹120')
            ->assertSee('bi-diamond-fill')
            ->assertSee('bi-gem');

        $this->assertStringNotContainsString('100.00000000', $response->getContent());
        $this->assertSame(100.0, (float) $rate->fresh()->rate);

        $rate->update(['rate' => '100.50000000']);

        $this->get(route('exchange'))
            ->assertOk()
            ->assertSee('1 USDT = 100.5 INR')
            ->assertSee('₹100.5');

        $this->assertSame(100.5, (float) $rate->fresh()->rate);
    }

    public function test_user_logout_returns_home_with_shared_notification_feedback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Signed out successfully.');

        $this->assertGuest('web');
        $this->get(route('home'))->assertSee('Signed out successfully.');
    }

    public function test_admin_two_factor_completion_redirects_to_admin_dashboard_not_stale_user_intended_url(): void
    {
        $secret = Totp::generateSecret();
        $admin = User::factory()->create([
            'role' => 'admin',
            'totp_enabled' => true,
            'totp_secret' => $secret,
        ]);

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('admin.login.store'), [
                'email' => $admin->email,
                'password' => 'password',
            ])->assertRedirect(route('admin.2fa.challenge'));

        $this->post(route('admin.2fa.verify'), ['code' => $this->totpCode($secret)])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_expired_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subHours(2)]);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'AnotherStrong!Password123',
            'password_confirmation' => 'AnotherStrong!Password123',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('AnotherStrong!Password123', $user->fresh()->password));
    }

    public function test_admin_password_reset_uses_admin_flow_and_audits_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = Password::createToken($admin);

        $this->post(route('admin.password.update'), [
            'email' => $admin->email,
            'token' => $token,
            'password' => 'NewAdmin!Password123',
            'password_confirmation' => 'NewAdmin!Password123',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('NewAdmin!Password123', $admin->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.password_reset']);
    }

    public function test_admin_seeder_creates_a_verified_active_admin_from_environment_password(): void
    {
        config(['admin.password' => 'SeederStrong!Password123']);

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@quickpaymoney.com')->firstOrFail();
        $this->assertSame('Quick PayMoney Admin', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->account_status);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertFalse($admin->totp_enabled);
        $this->assertTrue(Hash::check('SeederStrong!Password123', $admin->password));
        $this->assertSame(url('/admin/dashboard'), route('admin.dashboard'));

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'SeederStrong!Password123',
        ])->assertRedirect(route('admin.2fa.setup'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_seeder_does_not_promote_an_existing_customer(): void
    {
        User::factory()->create(['email' => 'admin@quickpaymoney.com']);
        config(['admin.password' => 'SeederStrong!Password123']);

        try {
            (new AdminSeeder)->run();
            $this->fail('The admin seeder must not promote a customer account.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('existing customer account', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', ['email' => 'admin@quickpaymoney.com', 'role' => 'user']);
    }

    public function test_admin_seeder_does_not_overwrite_an_existing_admin(): void
    {
        $admin = User::factory()->create([
            'name' => 'Existing Admin',
            'email' => 'admin@quickpaymoney.com',
            'role' => 'admin',
            'account_status' => 'suspended',
            'email_verified_at' => null,
            'password' => 'Existing!Password123',
            'totp_enabled' => true,
            'totp_secret' => Totp::generateSecret(),
        ]);
        $originalPasswordHash = $admin->password;
        config(['admin.password' => 'SeederStrong!Password456']);

        $this->seed(AdminSeeder::class);

        $admin->refresh();
        $this->assertSame('Existing Admin', $admin->name);
        $this->assertSame('suspended', $admin->account_status);
        $this->assertNull($admin->email_verified_at);
        $this->assertSame($originalPasswordHash, $admin->password);
        $this->assertTrue(Hash::check('Existing!Password123', $admin->password));
        $this->assertTrue($admin->totp_enabled);
        $this->assertNotNull($admin->totp_secret);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_seeder_requires_environment_credentials(): void
    {
        config(['admin.password' => null]);

        try {
            (new AdminSeeder)->run();
            $this->fail('The admin seeder must reject missing credentials.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ADMIN_PASSWORD', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_seeder_refuses_non_mysql_databases_outside_tests(): void
    {
        config([
            'app.env' => 'local',
            'admin.password' => 'SeederStrong!Password123',
        ]);

        try {
            (new AdminSeeder)->run();
            $this->fail('The admin seeder must require MySQL outside automated tests.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('DB_CONNECTION=mysql', $exception->getMessage());
        } finally {
            config(['app.env' => 'testing']);
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_guard_provider_only_retrieves_admin_accounts(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = Auth::guard('admin')->getProvider();

        $this->assertNull($provider->retrieveById($customer->id));
        $this->assertSame($admin->id, $provider->retrieveById($admin->id)->id);
    }

    public function test_shared_alerts_render_validation_errors_and_escape_flash_content(): void
    {
        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => 'missing@example.test',
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('quickpay-notifications')
            ->assertSee('These credentials do not match our records.');

        $this->withSession(['status' => '<img src=x onerror=alert(1)>'])
            ->get(route('admin.login'))
            ->assertOk()
            ->assertSee('\\u003Cimg src=x onerror=alert(1)\\u003E', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_customer_and_admin_credentials_are_isolated_between_login_pages(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.test',
            'password' => 'CustomerStrong!Password123',
        ]);
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'email_verified_at' => null,
            'role' => 'admin',
            'password' => 'AdminStrong!Password123',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $customer->email,
            'password' => 'CustomerStrong!Password123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest('admin');

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest('web');

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertRedirect(route('admin.2fa.setup'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');

        Auth::guard('admin')->logout();
        $this->actingAs($customer, 'admin')->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_login_rate_limit_is_keyed_to_email_and_ip(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
    }

    public function test_totp_matches_rfc6238_sha1_vector(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame(1, Totp::matchingCounter($secret, '287082', 59));
        $this->assertTrue(Totp::verify($secret, '287082', 59));
        $this->assertFalse(Totp::verify($secret, '000000', 59));
    }

    public function test_admin_login_requires_enrollment_and_second_factor_before_admin_routes(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
            'password' => 'AdminStrong!Password123',
        ]);
        $admin->forceFill(['totp_secret' => Totp::generateSecret()])->save();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertRedirect(route('admin.2fa.setup'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.setup'));

        $secret = $admin->fresh()->totp_secret;
        $this->post(route('admin.2fa.enable'), ['code' => $this->totpCode($secret)])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Administration overview');
        $this->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertRedirect(route('admin.2fa.challenge'));
        $this->post(route('admin.2fa.verify'), ['code' => $this->totpCode($secret)])
            ->assertSessionHasErrors('code');
    }

    public function test_local_debug_admin_demo_code_completes_setup_and_challenge(): void
    {
        config([
            'admin.2fa_demo' => true,
            'app.env' => 'local',
            'app.debug' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'password' => 'AdminStrong!Password123',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertRedirect(route('admin.2fa.setup'));

        $this->get(route('admin.2fa.setup'))
            ->assertOk()
            ->assertSee('Local development demo code (valid for 5 minutes):');
        $setupCode = $this->app['session.store']->get('admin_2fa_demo_code');
        $this->assertIsString($setupCode);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $setupCode);
        $setupCodeExpiry = $this->app['session.store']->get('admin_2fa_demo_expires_at');
        $this->travel(6)->minutes();
        $this->post(route('admin.2fa.enable'), ['code' => $setupCode])
            ->assertSessionHasErrors('code');
        $this->get(route('admin.2fa.setup'))->assertOk();
        $setupCode = $this->app['session.store']->get('admin_2fa_demo_code');
        $this->assertGreaterThan($setupCodeExpiry, $this->app['session.store']->get('admin_2fa_demo_expires_at'));
        $this->post(route('admin.2fa.enable'), ['code' => $setupCode])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertTrue($admin->fresh()->totp_enabled);
        $this->assertNotNull($admin->fresh()->totp_secret);

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertRedirect(route('admin.2fa.challenge'));

        $this->get(route('admin.2fa.challenge'))
            ->assertOk()
            ->assertSee('Local development demo code (valid for 5 minutes):');
        $challengeCode = $this->app['session.store']->get('admin_2fa_demo_code');
        $this->assertIsString($challengeCode);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $challengeCode);
        $this->post(route('admin.2fa.verify'), ['code' => $challengeCode])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertNull($this->app['session.store']->get('admin_2fa_demo_code'));
    }

    public function test_production_never_displays_or_accepts_admin_demo_code_but_accepts_totp(): void
    {
        config([
            'admin.2fa_demo' => true,
            'app.env' => 'production',
            'app.debug' => true,
        ]);
        $secret = Totp::generateSecret();
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'AdminStrong!Password123',
            'totp_enabled' => true,
            'totp_secret' => $secret,
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'AdminStrong!Password123',
        ])->assertRedirect(route('admin.2fa.challenge'));
        $this->get(route('admin.2fa.challenge'))
            ->assertOk()
            ->assertDontSee('Local development demo code')
            ->assertDontSee('admin_2fa_demo_code');
        $this->assertNull($this->app['session.store']->get('admin_2fa_demo_code'));

        $invalidCode = null;

        for ($number = 0; $number < 1_000_000; $number++) {
            $candidate = str_pad((string) $number, 6, '0', STR_PAD_LEFT);

            if (! Totp::verify($secret, $candidate)) {
                $invalidCode = $candidate;
                break;
            }
        }

        $this->assertNotNull($invalidCode);
        $this->post(route('admin.2fa.verify'), ['code' => $invalidCode])
            ->assertSessionHasErrors('code');
        $this->post(route('admin.2fa.verify'), ['code' => $this->totpCode($secret)])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_local_admin_demo_code_is_disabled_when_debug_is_off(): void
    {
        config([
            'admin.2fa_demo' => true,
            'app.env' => 'local',
            'app.debug' => false,
        ]);
        $secret = Totp::generateSecret();
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'totp_secret' => $secret,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.2fa.setup'))
            ->assertOk()
            ->assertDontSee('Local development demo code');
        $this->assertNull($this->app['session.store']->get('admin_2fa_demo_code'));
        $this->post(route('admin.2fa.enable'), ['code' => $this->totpCode($secret)])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_admin_dashboard_redirects_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
        $this->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_manage_users_and_exchange_statuses_with_audit(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
            'totp_enabled' => true,
            'totp_secret' => Totp::generateSecret(),
        ])->fresh();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $exchange = ExchangeRequest::create([
            'user_id' => $user->id,
            'usdt_amount' => '25.00000000',
            'exchange_rate' => '90.00000000',
            'inr_amount' => '2250.00',
            'status' => 'pending',
        ]);
        $this->actingAs($admin, 'admin')->withSession(['admin_2fa_verified' => true]);
        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('data-confirm="The selected role and account status will take effect immediately."', false);

        $this->put(route('admin.users.update', $user), [
            'account_status' => 'suspended',
            'role' => 'user',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'account_status' => 'suspended']);

        $this->put(route('admin.exchanges.update', $exchange), [
            'status' => 'completed',
            'transaction_reference' => 'TOO-EARLY-001',
            'admin_notes' => 'Invalid transition should not persist.',
        ])->assertStatus(422);
        $this->assertDatabaseHas('exchange_requests', ['id' => $exchange->id, 'status' => 'pending']);

        $this->put(route('admin.exchanges.update', $exchange), [
            'status' => 'processing',
            'transaction_reference' => '',
            'admin_notes' => 'Reviewing manually',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('exchange_requests', ['id' => $exchange->id, 'status' => 'processing']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.exchange_status_updated']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);

        $this->put(route('admin.exchanges.update', $exchange), [
            'status' => 'completed',
            'transaction_reference' => 'MANUAL-REF-001',
            'admin_notes' => 'Reference recorded; no settlement performed.',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('exchange_requests', ['id' => $exchange->id, 'status' => 'completed']);
    }

    public function test_every_admin_management_page_uses_the_branded_admin_shell(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'totp_enabled' => true,
            'totp_secret' => Totp::generateSecret(),
        ])->fresh();
        $user = User::factory()->create();
        $exchange = ExchangeRequest::create([
            'user_id' => $user->id,
            'usdt_amount' => '25.00000000',
            'exchange_rate' => '90.00000000',
            'inr_amount' => '2250.00',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin')->withSession(['admin_2fa_verified' => true]);

        foreach ([
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.users.show', $user),
            route('admin.exchanges.index'),
            route('admin.exchanges.show', $exchange),
            route('admin.rates.edit'),
            route('admin.profile'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('admin-sidebar')
                ->assertSee('admin-topbar')
                ->assertSee('Quick PayMoney')
                ->assertSee(route('admin.users.index'))
                ->assertSee(route('admin.exchanges.index'))
                ->assertSee(route('admin.rates.edit'))
                ->assertSee(route('admin.profile'));
        }
    }

    public function test_non_admin_cannot_access_admin_management(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user, 'admin')->get(route('admin.users.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_rate_update_stores_exact_decimal_and_audits_change(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'totp_enabled' => true,
            'totp_secret' => Totp::generateSecret(),
        ])->fresh();
        $this->actingAs($admin, 'admin')->withSession(['admin_2fa_verified' => true]);

        $this->put(route('admin.rates.update'), ['rate' => '90.12345678'])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('exchange_rates', ['pair' => 'USDT_INR', 'rate' => '90.12345678']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.exchange_rate_updated']);
        $this->get(route('admin.rates.edit'))->assertOk()->assertSee('Recent rate changes')->assertSee('90.12345678');
        $this->get(route('home'))->assertOk()->assertSee('90.12345678');
        $this->get(route('exchange'))->assertOk()->assertSee('90.12345678');
    }

    private function totpCode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($secret) as $character) {
            $bits .= str_pad(decbin(strpos($alphabet, $character)), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $key .= chr(bindec($byte));
            }
        }

        $counter = intdiv(time(), 30);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $number = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($number % 1_000_000), 6, '0', STR_PAD_LEFT);
    }
}
