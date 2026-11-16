<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\LivestockContestHistory;
use App\Models\LivestockProduct;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function sellerWithActiveStore(): User
    {
        $owner = User::factory()->create(['name' => 'Slamet Riyadi']);
        Store::create([
            'user_id' => $owner->id,
            'name' => 'Peternakan Sumber Rejeki',
            'business_categories' => ['ternak_besar'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Glagah, Banyuwangi',
            'status' => StoreStatus::Active,
        ]);

        return $owner;
    }

    public function test_peternak_dengan_toko_aktif_dapat_membuka_dasbor(): void
    {
        $this->actingAs($this->sellerWithActiveStore())
            ->get(route('seller.dasbor'))
            ->assertOk()
            ->assertSeeText('Selamat datang, Slamet Riyadi')
            ->assertSeeText('Peternakan Sumber Rejeki')
            ->assertSeeText('Pendapatan 6 bulan terakhir')
            ->assertSeeText('Pesanan terbaru')
            ->assertSeeText('Tambah Hasil Ternak')
            ->assertSeeText('Tambah Ternak')
            ->assertSeeText('0 data')
            ->assertSeeText('0 individu · 0 batch')
            ->assertSeeText('0 produk')
            ->assertDontSeeText('Perlu tindakan')
            ->assertSee('aria-current="page"', false);
    }

    public function test_dasbor_menghitung_data_toko_saja_dan_menautkan_tindakan(): void
    {
        $owner = $this->sellerWithActiveStore();
        $store = Store::query()->where('user_id', $owner->id)->firstOrFail();
        $otherOwner = $this->sellerWithActiveStore();
        $otherStore = Store::query()->where('user_id', $otherOwner->id)->firstOrFail();

        $livestockCategory = CommodityCategory::query()->create([
            'group' => 'ternak_hidup',
            'name' => 'Ternak Besar',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $livestockType = CommodityType::query()->create([
            'category_id' => $livestockCategory->id,
            'name' => 'Sapi Limousin',
            'selling_modes' => ['individual', 'batch'],
            'purposes' => ['bibit'],
            'form_fields' => CommodityType::DEFAULT_FORM_FIELDS,
            'is_active' => true,
        ]);
        $this->createLivestock($store, $livestockType, 'individual', 'aktif', 1);
        $batchLivestock = $this->createLivestock($store, $livestockType, 'batch', 'aktif', 5);
        $this->createLivestock($store, $livestockType, 'batch', 'nonaktif', 2);
        $this->createLivestock($otherStore, $livestockType, 'individual', 'aktif', 1);

        $productCategory = CommodityCategory::query()->create([
            'group' => 'hasil_ternak',
            'name' => 'Produk Susu',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $productType = CommodityType::query()->create([
            'category_id' => $productCategory->id,
            'name' => 'Susu Segar',
            'selling_modes' => ['produk'],
            'purposes' => [],
            'form_fields' => [],
            'is_active' => true,
        ]);
        $almostExpired = $this->createProduct($store, $productType, today()->addDays(3), 4);
        $this->createProduct($store, $productType, today()->addDays(8), 20);
        $this->createProduct($otherStore, $productType, today()->addDays(2), 3);

        $this->createPendingContest($batchLivestock);
        $otherLivestock = Livestock::query()
            ->where('store_id', $otherStore->id)
            ->firstOrFail();
        $this->createPendingContest($otherLivestock);

        $this->actingAs($owner)
            ->get(route('seller.dasbor'))
            ->assertOk()
            ->assertSeeText('2 data')
            ->assertSeeText('1 individu · 1 batch')
            ->assertSeeText('1 produk')
            ->assertSeeText('1 produk kedaluwarsa dalam 3 hari')
            ->assertSeeText('Susu Segar')
            ->assertSeeText('1 ternak batch memiliki stok rendah')
            ->assertSeeText('Sapi Limousin')
            ->assertSeeText('1 produk memiliki stok rendah')
            ->assertSeeText('1 sertifikat menunggu verifikasi')
            ->assertSee('href="'.route('seller.hasil-ternak.index').'"', false)
            ->assertSee('href="'.route('seller.ternak.index').'"', false)
            ->assertDontSeeText('2 produk kedaluwarsa dalam 3 hari')
            ->assertDontSeeText('2 sertifikat menunggu verifikasi');

        $this->assertSame('hampir_kedaluwarsa', $almostExpired->status);
    }

    public function test_profil_toko_tetap_dapat_dibuka_dengan_layout_seller(): void
    {
        $this->actingAs($this->sellerWithActiveStore())
            ->get(route('seller.toko.profil'))
            ->assertOk()
            ->assertSeeText('Profil toko')
            ->assertSeeText('Informasi toko')
            ->assertSeeText('Penjual')
            ->assertSee('aria-current="page"', false);
    }

    public function test_mode_libur_tetap_dapat_dibuka_dengan_layout_seller(): void
    {
        $this->actingAs($this->sellerWithActiveStore())
            ->get(route('seller.toko.libur'))
            ->assertOk()
            ->assertSeeText('Mode libur')
            ->assertSeeText('Pengaturan mode libur')
            ->assertSeeText('Penjual')
            ->assertSee('aria-current="page"', false);
    }

    private function createLivestock(
        Store $store,
        CommodityType $type,
        string $mode,
        string $status,
        int $stock
    ): Livestock {
        return Livestock::query()->create([
            'store_id' => $store->id,
            'commodity_type_id' => $type->id,
            'mode' => $mode,
            'purpose' => 'bibit',
            'nama_kode' => $mode === 'batch' ? 'Sapi Limousin' : null,
            'harga' => 100000,
            'satuan_jual' => 'ekor',
            'stok' => $stock,
            'status' => $status,
        ]);
    }

    private function createProduct(Store $store, CommodityType $type, Carbon $expiry, int $stock): LivestockProduct
    {
        return LivestockProduct::query()->create([
            'store_id' => $store->id,
            'commodity_type_id' => $type->id,
            'stok' => $stock,
            'satuan' => 'liter',
            'harga' => 18000,
            'tanggal_produksi' => today()->subDay(),
            'tanggal_kedaluwarsa' => $expiry,
            'penyimpanan_dingin' => true,
        ]);
    }

    private function createPendingContest(Livestock $livestock): LivestockContestHistory
    {
        return LivestockContestHistory::query()->create([
            'livestock_id' => $livestock->id,
            'contest_name' => 'Kontes Ternak',
            'category' => 'Postur',
            'rank' => 'Juara 1',
            'contest_date' => today()->subMonth(),
            'organizer' => 'Dinas Peternakan',
            'certificate_path' => 'sertifikat.pdf',
            'certificate_original_name' => 'sertifikat.pdf',
            'certificate_mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
    }
}
