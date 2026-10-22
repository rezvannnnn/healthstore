<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\PaymentService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $inventory = Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10, 'is_active' => true]);
        $order = Order::create(['user_id' => $user->id, 'order_number' => 'TEST-'.uniqid(), 'status' => 'pending', 'payment_status' => 'pending', 'subtotal' => 5000, 'total_amount' => 5000, 'currency' => 'IRR']);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 2, 'unit_price' => 2500, 'total_amount' => 5000]);
        $reservations = app(InventoryReservationService::class)->reserve($order, $product, 2);
        $payment = $order->payments()->create(['amount' => 5000, 'status' => 'pending', 'authority' => 'TEST-AUTH']);
        $gateway = new class implements PaymentGatewayInterface
        {
            public int $requests = 0;

            public int $verifications = 0;

            public function request(Payment $payment): array
            {
                $this->requests++;

                return ['authority' => 'TEST-AUTH', 'payment_url' => 'https://gateway.test/payment'];
            }

            public function verify(Payment $payment, array $callbackData): array
            {
                $this->verifications++;

                return ['verified' => true, 'success' => true, 'transaction_id' => 'BANK-VERIFIED', 'reference_number' => 'BANK-REF'];
            }

            public function paymentUrl(array $gatewayData): string
            {
                return 'https://gateway.test/payment';
            }
        };
        $service = new PaymentService(app(InventoryReservationService::class), $gateway);

        return compact('user', 'product', 'inventory', 'order', 'reservations', 'payment', 'gateway', 'service');
    }

    public function test_expired_payment_is_blocked_before_contacting_gateway(): void
    {
        $f = $this->fixture();
        $this->travel(21)->minutes();
        try {
            $f['service']->requestGatewayPayment($f['payment']);
            $this->fail('Expired reservation accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('منقضی', $exception->getMessage());
        }
        $this->assertSame(0, $f['gateway']->requests);
    }

    public function test_bank_confirmation_after_cleanup_is_preserved_for_review(): void
    {
        $f = $this->fixture();
        $this->travel(21)->minutes();
        app(InventoryReservationService::class)->releaseExpired();
        $result = $f['service']->verifyAndFinalizeGatewayPayment($f['payment'], []);
        $this->assertSame('requires_review', $result['status']);
        $this->assertSame('BANK-VERIFIED', $f['payment']->fresh()->transaction_id);
        $this->assertNotNull($f['payment']->fresh()->paid_at);
        $this->assertSame('pending', $f['order']->fresh()->payment_status);
        $this->assertSame(10, $f['inventory']->fresh()->quantity);
        $f['service']->verifyAndFinalizeGatewayPayment($f['payment'], []);
        $this->assertSame(1, $f['gateway']->verifications);
    }

    public function test_stock_consumption_failure_does_not_lose_bank_confirmation(): void
    {
        $f = $this->fixture();
        $f['inventory']->update(['quantity' => 0]);
        $result = $f['service']->verifyAndFinalizeGatewayPayment($f['payment'], []);
        $this->assertSame('requires_review', $result['status']);
        $this->assertSame('BANK-VERIFIED', $f['payment']->fresh()->transaction_id);
        $this->assertSame('pending', $f['order']->fresh()->payment_status);
    }

    public function test_payment_without_complete_reservation_never_marks_order_paid(): void
    {
        $f = $this->fixture();
        $f['order']->inventoryReservations()->update(['status' => 'released']);
        $this->expectException(\RuntimeException::class);
        $f['service']->markAsPaid($f['payment'], 'BANK-ID');
    }

    public function test_disabled_gateway_does_not_create_payment_or_send_http_request(): void
    {
        $f = $this->fixture();
        $f['payment']->delete();
        config(['services.payment.provider' => 'disabled']);
        $this->actingAs($f['user'])->from('/orders/'.$f['order']->order_number)->post('/orders/'.$f['order']->order_number.'/payment')->assertRedirect()->assertSessionHas('info');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_guest_selection_is_kept_for_login(): void
    {
        $product = Product::factory()->create();
        $this->post('/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect('/login')->assertSessionHas('cart_intent.product_id', $product->id)->assertSessionHas('cart_intent.quantity', 2);
    }

    public function test_catalog_query_count_does_not_grow_per_product(): void
    {
        Product::factory()->count(12)->create();
        DB::enableQueryLog();
        $response = $this->get('/products');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $response->assertOk();
        $this->assertLessThan(20, count($queries));
    }

    public function test_json_ld_exists_in_initial_html_with_farsi_language(): void
    {
        config(['inertia.ssr.enabled' => false]);
        $this->get('/')->assertOk()->assertSee('lang="fa"', false)->assertSee('application/ld+json', false)->assertSee('داروخونه');
    }

    public function test_slug_change_redirects_and_moves_automatic_canonical(): void
    {
        $product = Product::factory()->create(['slug' => 'old-address', 'canonical_url' => url('/products/old-address')]);
        $product->update(['slug' => 'new-address']);
        $this->assertSame(url('/products/new-address'), $product->fresh()->canonical_url);
        $this->get('/products/old-address')->assertStatus(301)->assertRedirect('/products/new-address');
    }

    public function test_markdown_strips_html_and_unsafe_links(): void
    {
        $html = Str::markdown('## Heading'."\n<script>alert(1)</script>\n[unsafe](javascript:alert(1))", ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function test_totp_matches_rfc_vectors(): void
    {
        $service = app(TwoFactorService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        foreach ([59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471', 1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'] as $time => $code) {
            $this->assertSame($code, $service->code($secret, $time, 8));
        }
    }

    public function test_totp_and_recovery_codes_cannot_be_replayed(): void
    {
        $service = app(TwoFactorService::class);
        $user = User::factory()->create();
        $secret = $service->generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = [Hash::make('single-use-recovery')];
        $user->save();
        $this->assertTrue($service->verify($user, $service->code($secret, now()->getTimestamp())));
        $this->assertFalse($service->verify($user, $service->code($secret, now()->getTimestamp())));
        $this->assertTrue($service->verify($user, 'single-use-recovery'));
        $this->assertFalse($service->verify($user, 'single-use-recovery'));
        $this->assertStringNotContainsString($secret, $user->fresh()->toJson());
        $this->assertNotSame($secret, $user->fresh()->getRawOriginal('two_factor_secret'));
    }

    public function test_admin_with_mfa_cannot_login_using_only_password(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_admin' => true, 'admin_active' => true, 'admin_username' => 'mfa-admin', 'password' => 'Test-only-Password1!']);
        $user->two_factor_secret = app(TwoFactorService::class)->generateSecret();
        $user->two_factor_confirmed_at = now();
        $user->save();
        $this->post('/admin/login', ['username' => 'mfa-admin', 'password' => 'Test-only-Password1!'])->assertSessionHasErrors('two_factor_code');
        $this->assertGuest();
    }

    public function test_reused_slug_has_no_stale_redirect(): void
    {
        $original = Product::factory()->create(['slug' => 'reusable-address']);
        $original->update(['slug' => 'moved-address']);
        Product::factory()->create(['slug' => 'reusable-address']);
        $this->get('/products/reusable-address')->assertOk();
    }

    public function test_changed_admin_password_invalidates_old_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true, 'admin_active' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk();
        $admin->update(['password' => 'Replacement-Password123!']);
        $this->get('/admin')->assertRedirect('/admin');
        $this->assertGuest();
    }

    public function test_admin_login_replaces_previous_customers_session_hash(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true, 'admin_active' => true, 'admin_username' => 'switch-admin', 'password' => 'Switch-Password123!']);
        $this->actingAs($customer)->get('/admin/login')->assertOk();
        $this->post('/admin/login', ['username' => 'switch-admin', 'password' => 'Switch-Password123!'])->assertRedirect();
        $this->get('/admin/products')->assertOk();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_mfa_enrollment_requires_password_and_valid_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true, 'admin_active' => true, 'password' => 'Enroll-Password123!']);
        $this->actingAs($admin)->from('/admin/security')->post('/admin/security/prepare', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/admin/security/prepare', ['password' => 'Enroll-Password123!'])->assertRedirect();
        $secret = session('mfa.pending_secret');
        $this->assertIsString($secret);
        $this->post('/admin/security/enable', ['password' => 'Enroll-Password123!', 'code' => app(TwoFactorService::class)->code($secret, now()->getTimestamp())])->assertRedirect();
        $admin->refresh();
        $this->assertNotNull($admin->two_factor_confirmed_at);
        $this->assertCount(8, $admin->two_factor_recovery_codes);
        $codes = session('mfa.recovery_codes');
        $this->assertTrue(Hash::check($codes[0], $admin->two_factor_recovery_codes[0]));
        $this->assertFalse(app(TwoFactorService::class)->verify($admin, app(TwoFactorService::class)->code($secret, now()->getTimestamp())));
        $this->post('/admin/security/disable', ['password' => 'Enroll-Password123!', 'code' => $codes[0]])->assertRedirect();
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);
    }

    public function test_csv_export_is_utf8_and_escapes_formula_names(): void
    {
        $fixture = $this->fixture();
        $fixture['product']->update(['name' => '=2+3']);
        $fixture['order']->update(['status' => 'paid', 'payment_status' => 'paid', 'paid_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true, 'admin_active' => true]);
        $response = $this->actingAs($admin)->get('/admin/reports?format=csv');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=2+3", $csv);
        $this->assertStringContainsString('5000', $csv);
    }
}
