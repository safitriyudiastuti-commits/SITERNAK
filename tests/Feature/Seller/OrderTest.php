<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\LivestockProduct;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_and_paginates_only_orders_for_the_active_store(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();

        for ($i = 1; $i <= 11; $i++) {
            $this->createOrder($store, 'menunggu_konfirmasi', sprintf('ORD-OWN-%02d', $i), now()->addSeconds($i));
        }
        $this->createOrder($store, 'dikonfirmasi', 'ORD-OWN-CONFIRMED');
        $this->createOrder($otherStore, 'menunggu_konfirmasi', 'ORD-OTHER');

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'menunggu_konfirmasi']))
            ->assertOk()
            ->assertSeeText('ORD-OWN-11')
            ->assertDontSeeText('ORD-OWN-01')
            ->assertDontSeeText('ORD-OWN-CONFIRMED')
            ->assertDontSeeText('ORD-OTHER')
            ->assertSee('tab=menunggu_konfirmasi', false)
            ->assertSeeText('Menunggu konfirmasi');

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'menunggu_konfirmasi', 'page' => 2]))
            ->assertOk()
            ->assertSeeText('ORD-OWN-01')
            ->assertDontSeeText('ORD-OTHER');
    }

    public function test_tabs_filter_orders_by_payment_stage_and_unknown_tab_defaults_to_all(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();

        $pending = $this->createOrder($store, 'menunggu_konfirmasi', 'ORD-PENDING', null, 'sah');
        $confirmedUnpaid = $this->createOrder($store, 'dikonfirmasi', 'ORD-CONFIRMED-UNPAID', null, 'belum');
        $confirmedVerifying = $this->createOrder($store, 'dikonfirmasi', 'ORD-CONFIRMED-VERIFY', null, 'menunggu_verifikasi');
        $verifying = $this->createOrder($store, 'diproses', 'ORD-VERIFY', null, 'menunggu_verifikasi');
        $paid = $this->createOrder($store, 'dikirim', 'ORD-PAID', null, 'sah');
        $completed = $this->createOrder($store, 'selesai', 'ORD-COMPLETED', null, 'sah');
        $rejected = $this->createOrder($store, 'ditolak', 'ORD-REJECTED', null, 'sah');
        $cancelled = $this->createOrder($store, 'dibatalkan', 'ORD-CANCELLED', null, 'menunggu_verifikasi');
        $foreignOrder = $this->createOrder($otherStore, 'dikonfirmasi', 'ORD-FOREIGN', null, 'belum');

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'dikonfirmasi']))
            ->assertOk()
            ->assertSeeText($confirmedUnpaid->nomor_order)
            ->assertDontSeeText($confirmedVerifying->nomor_order)
            ->assertDontSeeText($foreignOrder->nomor_order);

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'menunggu_verifikasi_bayar']))
            ->assertOk()
            ->assertSeeText($confirmedVerifying->nomor_order)
            ->assertSeeText($verifying->nomor_order)
            ->assertDontSeeText($cancelled->nomor_order);

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'lunas']))
            ->assertOk()
            ->assertSeeText($pending->nomor_order)
            ->assertSeeText($paid->nomor_order)
            ->assertDontSeeText($completed->nomor_order)
            ->assertDontSeeText($rejected->nomor_order);

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'selesai']))
            ->assertOk()
            ->assertSeeText($completed->nomor_order)
            ->assertDontSeeText($paid->nomor_order);

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'not-a-tab']))
            ->assertOk()
            ->assertSeeText($pending->nomor_order)
            ->assertSeeText($confirmedUnpaid->nomor_order)
            ->assertSeeText($completed->nomor_order)
            ->assertDontSeeText($foreignOrder->nomor_order)
            ->assertSee('aria-current="page"', false);
    }

    public function test_index_shows_store_scoped_tab_counts(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();
        $this->createOrder($store, 'menunggu_konfirmasi', 'ORD-COUNT-PENDING');
        $this->createOrder($store, 'dikonfirmasi', 'ORD-COUNT-CONFIRMED', null, 'belum');
        $this->createOrder($store, 'diproses', 'ORD-COUNT-VERIFY', null, 'menunggu_verifikasi');
        $this->createOrder($store, 'dikirim', 'ORD-COUNT-PAID', null, 'sah');
        $this->createOrder($store, 'selesai', 'ORD-COUNT-DONE', null, 'sah');
        $this->createOrder($otherStore, 'menunggu_konfirmasi', 'ORD-COUNT-FOREIGN');

        $response = $this->actingAs($seller)
            ->get(route('seller.pesanan.index'))
            ->assertOk()
            ->assertSeeText('Semua')
            ->assertSeeText('Menunggu verifikasi bayar')
            ->assertSeeText('Lunas')
            ->assertSeeText('Selesai')
            ->assertDontSeeText('ORD-COUNT-FOREIGN');

        $this->assertMatchesRegularExpression(
            '/href="[^"]*tab=semua"[^>]*>.*?<span[^>]*>\s*5\s*<\/span>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/href="[^"]*tab=menunggu_konfirmasi"[^>]*>.*?<span[^>]*>\s*1\s*<\/span>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/href="[^"]*tab=menunggu_verifikasi_bayar"[^>]*>.*?<span[^>]*>\s*1\s*<\/span>/s',
            $response->getContent()
        );
    }

    public function test_index_shows_cod_and_real_inventory_notes_without_false_claims(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $type = $this->livestockType();
        $availableLivestock = Livestock::query()->create([
            'store_id' => $store->id,
            'commodity_type_id' => $type->id,
            'mode' => 'batch',
            'nama_kode' => 'Sapi tersedia',
            'harga' => 100000,
            'satuan_jual' => 'ekor',
            'stok' => 7,
            'status' => 'aktif',
        ]);
        $shortProduct = LivestockProduct::query()->create([
            'store_id' => $store->id,
            'commodity_type_id' => $type->id,
            'stok' => 2.5,
            'satuan' => 'kg',
            'harga' => 50000,
            'tanggal_produksi' => today()->subDay(),
            'tanggal_kedaluwarsa' => today()->addDays(10),
        ]);
        $codSameCity = $this->createOrder($store, 'menunggu_konfirmasi', 'ORD-COD-SAME', null, 'belum', 'cod', 'Kabupaten Banyuwangi');
        $codSameCity->update(['livestock_id' => $availableLivestock->id]);
        $codOtherCity = $this->createOrder($store, 'menunggu_konfirmasi', 'ORD-COD-OTHER', null, 'belum', 'cod', 'Jember');
        $codNoCity = $this->createOrder($store, 'menunggu_konfirmasi', 'ORD-COD-NO-CITY', null, 'belum', 'cod', '');
        $shortStock = $this->createOrder($store, 'menunggu_konfirmasi', 'ORD-SHORT-STOCK');
        $shortStock->update([
            'livestock_product_id' => $shortProduct->id,
            'jumlah' => 3,
            'satuan' => 'kg',
        ]);

        $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'menunggu_konfirmasi']))
            ->assertOk()
            ->assertSeeText('Pembeli satu kota dengan Anda, sehingga COD tersedia')
            ->assertSeeText('Stok tersedia 7 ekor, cukup untuk pesanan ini')
            ->assertSeeText('Stok tersedia 2,5 kg, stok tidak cukup')
            ->assertSeeText($codSameCity->nomor_order)
            ->assertSeeText($codOtherCity->nomor_order)
            ->assertSeeText($codNoCity->nomor_order)
            ->assertSee(route('seller.pesanan.confirm', $codSameCity), false)
            ->assertSee(route('seller.pesanan.reject', $codSameCity), false);

        $responseText = $this->actingAs($seller)
            ->get(route('seller.pesanan.index', ['tab' => 'menunggu_konfirmasi']))
            ->getContent();
        $otherCityCard = substr($responseText, strpos($responseText, $codOtherCity->nomor_order));
        $otherCityCard = substr($otherCityCard, 0, strpos($otherCityCard, '</article>'));
        $this->assertStringNotContainsString('Pembeli satu kota dengan Anda, sehingga COD tersedia', $otherCityCard);

        $noCityCard = substr($responseText, strpos($responseText, $codNoCity->nomor_order));
        $noCityCard = substr($noCityCard, 0, strpos($noCityCard, '</article>'));
        $this->assertStringNotContainsString('Pembeli satu kota dengan Anda, sehingga COD tersedia', $noCityCard);
    }

    public function test_seller_cannot_open_an_order_from_another_store(): void
    {
        [$seller] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();
        $order = $this->createOrder($otherStore, 'menunggu_konfirmasi');

        $this->actingAs($seller)
            ->get(route('seller.pesanan.show', $order))
            ->assertNotFound();

        $this->actingAs($seller)
            ->post(route('seller.pesanan.confirm', $order))
            ->assertNotFound();
    }

    public function test_order_detail_shows_separate_order_and_payment_statuses(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'menunggu_konfirmasi');

        $this->actingAs($seller)
            ->get(route('seller.pesanan.show', $order))
            ->assertOk()
            ->assertSeeText($order->nomor_order)
            ->assertSeeText('Menunggu Konfirmasi')
            ->assertSeeText('Belum')
            ->assertSeeText('Informasi barang')
            ->assertSeeText('Data pembeli')
            ->assertSee(route('seller.pesanan.confirm', $order), false)
            ->assertSee(route('seller.pesanan.reject', $order), false)
            ->assertSee('href="'.route('seller.pesanan.index').'"', false);
    }

    public function test_confirm_updates_order_and_records_history(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'menunggu_konfirmasi');

        $this->actingAs($seller)
            ->post(route('seller.pesanan.confirm', $order))
            ->assertRedirect(route('seller.pesanan.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'dikonfirmasi',
            'status_bayar' => 'belum',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $seller->id,
            'status' => 'dikonfirmasi',
            'keterangan' => 'Penjual mengonfirmasi pesanan.',
        ]);
    }

    public function test_reject_requires_a_reason_and_records_rejection_history(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'menunggu_konfirmasi');

        $this->actingAs($seller)
            ->from(route('seller.pesanan.show', $order))
            ->post(route('seller.pesanan.reject', $order), ['alasan_penolakan' => ''])
            ->assertRedirect(route('seller.pesanan.show', $order))
            ->assertSessionHasErrors('alasan_penolakan');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'menunggu_konfirmasi',
        ]);

        $this->actingAs($seller)
            ->from(route('seller.pesanan.show', $order))
            ->post(route('seller.pesanan.reject', $order), ['alasan_penolakan' => str_repeat('x', 1001)])
            ->assertRedirect(route('seller.pesanan.show', $order))
            ->assertSessionHasErrors('alasan_penolakan');

        $reason = 'Stok sudah tidak tersedia.';
        $this->actingAs($seller)
            ->post(route('seller.pesanan.reject', $order), ['alasan_penolakan' => $reason])
            ->assertRedirect(route('seller.pesanan.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'ditolak',
            'alasan_penolakan' => $reason,
            'status_bayar' => 'belum',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_id' => $seller->id,
            'status' => 'ditolak',
            'keterangan' => 'Penjual menolak pesanan. Alasan: '.$reason,
        ]);
    }

    public function test_reject_validation_error_is_shown_in_the_list_dialog(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'menunggu_konfirmasi');

        $this->actingAs($seller)
            ->from(route('seller.pesanan.index'))
            ->followingRedirects()
            ->post(route('seller.pesanan.reject', $order), [
                'reject_order_id' => $order->id,
                'alasan_penolakan' => '',
            ])
            ->assertOk()
            ->assertSeeText('Alasan penolakan wajib diisi.')
            ->assertSee('reject-order-dialog-'.$order->id, false)
            ->assertSee('const rejectedOrderId = '.$order->id, false);
    }

    public function test_confirm_and_reject_are_rejected_after_order_leaves_pending_status(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'dikonfirmasi');

        $this->actingAs($seller)
            ->post(route('seller.pesanan.confirm', $order))
            ->assertStatus(409);

        $this->actingAs($seller)
            ->post(route('seller.pesanan.reject', $order), ['alasan_penolakan' => 'Tidak dapat dipenuhi'])
            ->assertStatus(409);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'dikonfirmasi',
            'alasan_penolakan' => null,
        ]);
        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'ditolak',
        ]);
    }

    public function test_order_change_rolls_back_if_status_history_cannot_be_created(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $order = $this->createOrder($store, 'menunggu_konfirmasi');

        OrderStatusHistory::creating(static function (): void {
            throw new RuntimeException('History write failed.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($seller)->post(route('seller.pesanan.confirm', $order));
            $this->fail('The failed history write should bubble up.');
        } catch (RuntimeException $exception) {
            $this->assertSame('History write failed.', $exception->getMessage());
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'menunggu_konfirmasi',
        ]);
        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'dikonfirmasi',
        ]);
    }

    /**
     * @return array{User, Store}
     */
    private function sellerWithStore(): array
    {
        $seller = User::factory()->create();
        $store = Store::create([
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

    private function createOrder(
        Store $store,
        string $status,
        ?string $number = null,
        mixed $createdAt = null,
        string $paymentStatus = 'belum',
        string $paymentMethod = 'transfer_bank',
        string $recipientCity = 'Banyuwangi'
    ): Order {
        $buyer = User::factory()->create();

        $order = Order::query()->create([
            'nomor_order' => $number ?? 'ORD-'.uniqid(),
            'buyer_id' => $buyer->id,
            'store_id' => $store->id,
            'livestock_id' => null,
            'livestock_product_id' => null,
            'nama_item' => 'Item '.$number,
            'tipe_item' => 'individual',
            'jumlah' => 1,
            'satuan' => 'ekor',
            'harga_satuan' => 100000,
            'total_harga' => 100000,
            'metode_bayar' => $paymentMethod,
            'status_bayar' => $paymentStatus,
            'status' => $status,
            'nama_penerima' => 'Penerima',
            'no_hp_penerima' => '08123456789',
            'alamat_penerima' => 'Alamat penerima',
            'kota_kabupaten_penerima' => $recipientCity,
            'provinsi_penerima' => 'Jawa Timur',
        ]);

        if ($createdAt !== null) {
            DB::table('orders')->where('id', $order->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return $order;
    }

    private function livestockType(): CommodityType
    {
        $category = CommodityCategory::query()->create([
            'group' => 'ternak_hidup',
            'name' => 'Ternak untuk pesanan',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return CommodityType::query()->create([
            'category_id' => $category->id,
            'name' => 'Ternak pesanan',
            'selling_modes' => ['individual', 'batch', 'produk'],
            'purposes' => ['bibit'],
            'form_fields' => CommodityType::DEFAULT_FORM_FIELDS,
            'is_active' => true,
        ]);
    }
}
