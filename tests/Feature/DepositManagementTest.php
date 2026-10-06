<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BalanceLedgerEntry;
use App\Models\Deposit;
use App\Models\DepositSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepositManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authenticated_admins_can_manage_private_payment_qr_settings(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_2fa_verified' => true])
            ->put(route('admin.deposit-settings.update'), [
                'recipient_name' => 'Quick PayMoney',
                'instructions' => 'Pay using the QR code and retain the UTR.',
                'qr_image' => UploadedFile::fake()->image('payment.png')->size(120),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Deposit settings updated.');

        $settings = DepositSettings::findOrFail(1);
        $this->assertSame('Quick PayMoney', $settings->recipient_name);
        $this->assertSame('Pay using the QR code and retain the UTR.', $settings->instructions);
        Storage::disk('local')->assertExists($settings->qr_path);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.deposit_qr_updated']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.deposit_settings_updated']);

        $this->get(route('admin.deposit-settings.qr'))->assertOk();
        $this->get(route('admin.deposit-settings.edit'))->assertOk()
            ->assertSee('Quick PayMoney')
            ->assertSee('Pay using the QR code and retain the UTR.')
            ->assertSee(route('admin.deposit-settings.qr'));

        $customer = User::factory()->create();
        auth('admin')->logout();
        $this->actingAs($customer, 'web')
            ->put(route('admin.deposit-settings.update'), [])
            ->assertRedirect(route('admin.login'));
        $this->assertSame($settings->qr_path, DepositSettings::findOrFail(1)->qr_path);
        $this->delete(route('admin.deposit-settings.destroy'))
            ->assertRedirect(route('admin.login'));
        $this->assertSame($settings->qr_path, DepositSettings::findOrFail(1)->qr_path);

        auth('admin')->logout();
        Storage::fake('local');
        $this->actingAs($admin, 'admin')
            ->withSession(['admin_2fa_verified' => true])
            ->put(route('admin.deposit-settings.update'), [
                'recipient_name' => 'Quick PayMoney',
                'instructions' => 'Updated instructions.',
                'remove_qr' => '1',
            ])
            ->assertRedirect();
        $this->assertNull(DepositSettings::findOrFail(1)->qr_path);
    }

    public function test_admin_can_replace_and_delete_payment_settings_without_removing_unrelated_files(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $settings = $this->configureQr();
        Storage::disk('local')->put('deposits/proofs/keep.png', 'private proof');
        $oldQrPath = $settings->qr_path;

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_2fa_verified' => true])
            ->put(route('admin.deposit-settings.update'), [
                'recipient_name' => 'Updated recipient',
                'instructions' => 'Updated payment steps.',
                'qr_image' => UploadedFile::fake()->image('replacement.webp')->size(180),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Deposit settings updated.');

        $settings->refresh();
        $newQrPath = $settings->qr_path;
        $this->assertNotSame($oldQrPath, $newQrPath);
        $this->assertSame('Updated recipient', $settings->recipient_name);
        $this->assertSame('Updated payment steps.', $settings->instructions);
        Storage::disk('local')->assertMissing($oldQrPath);
        Storage::disk('local')->assertExists($newQrPath);
        Storage::disk('local')->assertExists('deposits/proofs/keep.png');

        $this->get(route('admin.deposit-settings.edit'))
            ->assertOk()
            ->assertSee('Updated recipient')
            ->assertSee('Updated payment steps.')
            ->assertSee(route('admin.deposit-settings.qr'));

        $customer = User::factory()->create();
        $this->actingAs($customer, 'web')
            ->get(route('deposit.create'))
            ->assertOk()
            ->assertSee('Updated recipient')
            ->assertSee('Updated payment steps.')
            ->assertSee(route('deposit.payment-qr'))
            ->assertSee('Submit Deposit');

        $this->actingAs($admin, 'admin')->withSession(['admin_2fa_verified' => true]);
        $this->delete(route('admin.deposit-settings.destroy'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Deposit settings deleted.');

        $this->assertDatabaseMissing('deposit_settings', ['id' => 1]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.deposit_settings_deleted']);
        Storage::disk('local')->assertMissing($newQrPath);
        Storage::disk('local')->assertExists('deposits/proofs/keep.png');
    }

    public function test_missing_qr_file_shows_saved_details_but_disables_customer_submission(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create();
        $settings = DepositSettings::create([
            'id' => 1,
            'qr_path' => 'deposits/qr/missing.png',
            'recipient_name' => 'Saved recipient',
            'instructions' => 'Saved payment instructions.',
        ]);

        $this->actingAs($customer, 'web')
            ->get(route('deposit.create'))
            ->assertOk()
            ->assertSee('Saved recipient')
            ->assertSee('Saved payment instructions.')
            ->assertSee('Payment instructions are currently unavailable.')
            ->assertDontSee('Submit Deposit')
            ->assertDontSee(route('deposit.payment-qr'));

        $this->from(route('deposit.create'))
            ->post(route('deposit.store'), [
                'submission_key' => '35647b60-f6c2-4c67-9f50-2559066cf629',
                'amount' => '25.00',
                'transaction_reference' => 'UTRMISSINGQR01',
            ])
            ->assertRedirect(route('deposit.create'))
            ->assertSessionHasErrors('payment');
        $this->assertSame(0, Deposit::query()->count());

        $this->get(route('deposit.payment-qr'))->assertNotFound();

        Storage::disk('local')->put('deposits/proofs/private.png', 'private proof contents');
        $settings->update(['qr_path' => 'deposits/proofs/private.png']);
        $this->get(route('deposit.payment-qr'))
            ->assertNotFound()
            ->assertDontSee('private proof contents');

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.deposit-settings.edit'))
            ->assertOk()
            ->assertSee('The saved QR image is missing or outside the managed QR directory.')
            ->assertSee('Saved recipient');

        $this->assertSame(1, DepositSettings::query()->count());
        $this->assertSame('Saved recipient', $settings->fresh()->recipient_name);
    }

    public function test_customers_can_submit_pending_deposits_idempotently_without_balance_credit(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create();
        $this->configureQr();
        $this->actingAs($customer, 'web');

        $this->get(route('deposit.create'))
            ->assertOk()
            ->assertSee('Payment QR code')
            ->assertSee('Quick PayMoney')
            ->assertSee('Pay and keep your transaction reference.')
            ->assertSee('Submit Deposit')
            ->assertSee(route('deposit.payment-qr'));
        $this->get(route('deposit.payment-qr'))->assertOk();

        $payload = [
            'submission_key' => '35647b60-f6c2-4c67-9f50-2559066cf629',
            'amount' => '25.50',
            'transaction_reference' => 'UTR12345678',
            'payment_proof' => UploadedFile::fake()->image('proof.webp')->size(200),
        ];

        $response = $this->post(route('deposit.store'), $payload)
            ->assertRedirect();
        $deposit = Deposit::where('user_id', $customer->id)->firstOrFail();
        $response->assertRedirect(route('deposits.show', $deposit));
        $this->assertSame('pending', $deposit->status);
        $this->assertSame('25.50', $deposit->amount);
        $this->assertMatchesRegularExpression('/^REF-\d{8}-[A-Z0-9]{12}$/', $deposit->deposit_id);
        Storage::disk('local')->assertExists($deposit->proof_path);
        $this->assertSame('0.00', $customer->fresh()->balance);
        $this->assertDatabaseMissing('balance_ledger_entries', ['deposit_id' => $deposit->id]);

        $this->post(route('deposit.store'), $payload)->assertRedirect(route('deposits.show', $deposit));
        $this->assertSame(1, Deposit::count());
        $this->assertSame(1, AuditLog::where('event', 'deposit.submitted')->count());

        $this->from(route('deposit.create'))
            ->post(route('deposit.store'), [...$payload, 'submission_key' => 'b9812eac-29d0-43ea-a523-6084f338318e'])
            ->assertSessionHasErrors('transaction_reference');
        $this->assertSame(1, Deposit::count());

        $this->get(route('deposits.show', $deposit))
            ->assertOk()
            ->assertSee($deposit->deposit_id)
            ->assertSee('Your deposit request has been submitted successfully.')
            ->assertSee(route('deposits.proof', $deposit));
        $this->get(route('deposits.proof', $deposit))->assertOk();
    }

    public function test_approval_credits_exactly_once_and_rejection_never_credits(): void
    {
        Storage::fake('local');
        $this->configureQr();
        $customer = User::factory()->create();
        $first = $this->makeDeposit($customer, '50.25', 'UTRAPPROVE001');
        $second = $this->makeDeposit($customer, '18.00', 'UTRREJECT0001');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.deposits.index'))
            ->assertOk()
            ->assertSee($first->deposit_id)
            ->assertSee($customer->name);

        $this->get(route('admin.deposits.show', $first))
            ->assertOk()
            ->assertSee('verify the payment externally');

        $this->put(route('admin.deposits.update', $first), ['status' => 'approved'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Deposit request updated.');
        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('50.25', $customer->fresh()->balance);
        $this->assertSame(1, BalanceLedgerEntry::where('deposit_id', $first->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.deposit_approved']);

        $this->put(route('admin.deposits.update', $first), ['status' => 'approved'])
            ->assertRedirect()
            ->assertSessionHas('status', 'This deposit was already processed; no additional balance change was made.');
        $this->assertSame('50.25', $customer->fresh()->balance);
        $this->assertSame(1, BalanceLedgerEntry::where('deposit_id', $first->id)->count());

        $this->put(route('admin.deposits.update', $second), [
            'status' => 'rejected',
            'rejection_reason' => 'Payment could not be verified externally.',
        ])->assertRedirect();
        $this->assertSame('rejected', $second->fresh()->status);
        $this->assertSame('Payment could not be verified externally.', $second->fresh()->rejection_reason);
        $this->assertSame('50.25', $customer->fresh()->balance);
        $this->assertSame(1, BalanceLedgerEntry::where('user_id', $customer->id)->count());

        $this->expectException(\LogicException::class);
        BalanceLedgerEntry::firstOrFail()->update(['amount' => '1.00']);
    }

    public function test_profile_history_and_proofs_are_private_to_the_owning_customer(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $deposit = $this->makeDeposit($owner, '12.00', 'UTRPRIVATE001', 'deposits/proofs/owner.png');
        Storage::disk('local')->put($deposit->proof_path, 'private proof');

        $this->actingAs($owner, 'web')
            ->get(route('profile', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('Deposit History')
            ->assertSee($deposit->deposit_id)
            ->assertSee(route('deposits.show', $deposit));

        $this->get(route('deposits.show', $deposit))->assertOk();
        $this->get(route('deposits.proof', $deposit))->assertOk();
        $this->get(route('deposits.show', $this->makeDeposit($other, '4.00', 'UTRPRIVATE002')))
            ->assertForbidden();

        auth('web')->logout();
        $this->get(route('deposit.create'))->assertRedirect(route('login'));
        $this->get(route('deposit.payment-qr'))->assertRedirect(route('login'));
    }

    public function test_profile_deposit_history_search_filters_and_paginates_only_customer_records(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            $this->makeDeposit(
                $owner,
                '12.00',
                'UTRPROFILE'.str_pad((string) $index, 4, '0', STR_PAD_LEFT)
            );
        }

        $rejected = $this->makeDeposit($owner, '15.00', 'UTRPROFILE9999');
        $rejected->update([
            'status' => 'rejected',
            'rejection_reason' => 'Payment could not be verified.',
        ]);
        $this->makeDeposit($other, '99.00', 'UTRPROFILEOTHER');

        $this->actingAs($owner, 'web')
            ->get(route('profile', [
                'tab' => 'history',
                'search' => 'UTRPROFILE',
                'status' => 'pending',
            ]))
            ->assertOk()
            ->assertSee('Reference ID')
            ->assertSee('Amount (USD)')
            ->assertSee('Transaction ID')
            ->assertSee('Submitted Date')
            ->assertSee('Rejection Reason')
            ->assertSee('Details')
            ->assertSee('UTRPROFILE0001')
            ->assertDontSee('UTRPROFILEOTHER')
            ->assertSee('page=2');

        $this->get(route('profile', [
            'tab' => 'history',
            'search' => 'UTRPROFILE9999',
            'status' => 'rejected',
        ]))
            ->assertOk()
            ->assertSee('Payment could not be verified.')
            ->assertSee(route('deposits.show', $rejected))
            ->assertDontSee('UTRPROFILE0001');
    }

    public function test_recorded_balance_is_customer_specific_and_exchange_uses_shared_header(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['balance' => '125.40']);
        $guestExchange = $this->get(route('exchange'))->assertOk()->assertSee(route('login'))
            ->assertSee(route('contact'))
            ->assertSee('Sign in to view')
            ->assertDontSee('$125.40');

        $this->actingAs($customer, 'web')->get(route('exchange'))
            ->assertOk()
            ->assertSee('$125.40')
            ->assertSee('Not a wallet')
            ->assertSee(route('profile', ['tab' => 'history']))
            ->assertSee(route('logout'))
            ->assertSee(route('deposit.create'))
            ->assertSee('aria-label="Contact support"', false);

        $this->get(route('profile', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('Deposit History');
    }

    private function configureQr(): DepositSettings
    {
        Storage::disk('local')->put('deposits/qr/payment.png', 'private qr');

        return DepositSettings::create([
            'id' => 1,
            'qr_path' => 'deposits/qr/payment.png',
            'recipient_name' => 'Quick PayMoney',
            'instructions' => 'Pay and keep your transaction reference.',
        ]);
    }

    private function makeDeposit(User $user, string $amount, string $reference, ?string $proofPath = null): Deposit
    {
        return Deposit::create([
            'deposit_id' => 'REF-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(12)),
            'user_id' => $user->id,
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount' => $amount,
            'transaction_reference' => $reference,
            'proof_path' => $proofPath,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'totp_enabled' => true,
        ]);
    }
}
