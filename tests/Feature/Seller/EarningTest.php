<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-25 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_completed_orders_for_active_store_are_counted_including_unpaid_cod(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();
        $cod = $this->createOrder($store, 'selesai', 100000, 'belum', 'cod', '2026-09-07');
        $this->createOrder($store, 'diproses', 500000, 'sah', 'transfer_bank', '2026-09-10');
        $this->createOrder($otherStore, 'selesai', 900000, 'sah', 'transfer_bank', '2026-09-12');

        $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-09']))
            ->assertOk()
            ->assertSeeText($cod->nomor_order)
            ->assertSeeText('Belum lunas')
            ->assertDontSeeText('500.000')
            ->assertDontSeeText('900.000');
    }

    public function test_fees_are_rounded_per_order_and_week_boundaries_are_correct(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $this->createOrder($store, 'selesai', 50, 'sah', 'transfer_bank', '2026-09-07');
        $this->createOrder($store, 'selesai', 50, 'menunggu_verifikasi', 'transfer_bank', '2026-09-08');
        $this->createOrder($store, 'selesai', 100, 'sah', 'transfer_bank', '2026-09-21');
        $this->createOrder($store, 'selesai', 100, 'belum', 'cod', '2026-09-22');

        $response = $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-09']))->assertOk();
        $response->assertViewHas('summary', function (array $summary): bool {
            return $summary['gross'] === 300 && $summary['fee'] === 10 && $summary['net'] === 290;
        });
        $response->assertViewHas('weekly', [48, 48, 97, 97]);
    }

    public function test_month_uses_latest_completed_history_and_updated_at_fallback(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'selesai', 10000, 'sah', 'transfer_bank', '2026-08-31', false);
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'status' => 'selesai',
            'created_at' => '2026-09-03 10:00:00',
        ]);
        $fallback = $this->createOrder($store, 'selesai', 20000, 'sah', 'transfer_bank', '2026-09-09', false);

        $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-09']))
            ->assertOk()->assertSeeText($order->nomor_order)->assertSeeText($fallback->nomor_order);
        $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-08']))
            ->assertOk()->assertDontSeeText($order->nomor_order);
    }

    public function test_invalid_or_future_month_falls_back_to_current_month(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'selesai', 1000, 'sah', 'transfer_bank', '2026-09-01');

        foreach (['2026-13', 'not-a-month', '2026-10'] as $month) {
            $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => $month]))
                ->assertOk()->assertSeeText($order->nomor_order)->assertSee('value="2026-09" selected', false);
        }
    }

    public function test_month_over_month_change_is_correct_and_hidden_without_previous_net(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $this->createOrder($store, 'selesai', 10000, 'sah', 'transfer_bank', '2026-08-10');
        $this->createOrder($store, 'selesai', 20000, 'sah', 'transfer_bank', '2026-09-10');

        $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-09']))
            ->assertOk()->assertSeeText('+100% dari August');
        $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-08']))
            ->assertOk()->assertDontSeeText('+0% dari')->assertDontSeeText('+100% dari');
    }

    public function test_empty_month_shows_empty_states_without_fake_amounts(): void
    {
        [$seller] = $this->sellerWithStore();

        $this->actingAs($seller)->get(route('seller.penghasilan.index', ['bulan' => '2026-09']))
            ->assertOk()->assertSeeText('Belum ada pendapatan bersih pada bulan ini.')
            ->assertSeeText('Belum ada pesanan selesai pada bulan ini.')
            ->assertDontSeeText('Rata-rata Rp')
            ->assertViewHas('changePercent', null);
    }

    public function test_csv_exports_selected_store_and_escapes_formula_text(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'selesai', 10050, 'menunggu_verifikasi', 'transfer_bank', '2026-09-05');
        $order->update(['nama_item' => '=1+1']);
        $this->createOrder($otherStore, 'selesai', 50000, 'sah', 'transfer_bank', '2026-09-05');

        $response = $this->actingAs($seller)->get(route('seller.penghasilan.export', ['bulan' => '2026-09']))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename=penghasilan-2026-09.csv');
        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString(",'=1+1,", $body);
        $this->assertStringContainsString(',10050,302,9748,', $body);
        $this->assertStringNotContainsString('50,000', $body);
    }

    private function sellerWithStore(): array
    {
        $seller = User::factory()->create();
        $store = Store::query()->create([
            'user_id' => $seller->id,
            'name' => 'Toko '.$seller->id,
            'business_categories' => ['ternak_besar'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Alamat toko',
            'status' => StoreStatus::Active,
        ]);

        return [$seller, $store];
    }

    private function createOrder(Store $store, string $status, int $amount, string $paymentStatus, string $method, string $date, bool $history = true): Order
    {
        $order = Order::query()->create([
            'nomor_order' => 'ORD-'.strtoupper(bin2hex(random_bytes(5))),
            'buyer_id' => User::factory()->create()->id,
            'store_id' => $store->id,
            'nama_item' => 'Item '.$amount,
            'tipe_item' => 'individual',
            'jumlah' => 1,
            'satuan' => 'ekor',
            'harga_satuan' => $amount,
            'total_harga' => $amount,
            'metode_bayar' => $method,
            'status_bayar' => $paymentStatus,
            'status' => $status,
            'nama_penerima' => 'Penerima',
            'no_hp_penerima' => '08123456789',
            'alamat_penerima' => 'Alamat',
            'kota_kabupaten_penerima' => 'Banyuwangi',
            'provinsi_penerima' => 'Jawa Timur',
        ]);
        $order->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

        if ($history) {
            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $status,
                'created_at' => $date.' 12:00:00',
            ]);
        }

        return $order;
    }
}
