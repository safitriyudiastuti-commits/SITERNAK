<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestockPedigreeTest extends TestCase
{
    use RefreshDatabase;

    private CommodityCategory $category;

    private CommodityType $individualType;

    private CommodityType $batchType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CommodityCategory::query()->create([
            'group' => 'ternak_hidup',
            'name' => 'Ternak besar',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->individualType = CommodityType::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Sapi Limousin',
            'selling_modes' => ['individual'],
            'purposes' => ['bibit'],
            'form_fields' => CommodityType::DEFAULT_FORM_FIELDS,
            'is_active' => true,
        ]);
        $this->batchType = CommodityType::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Sapi kelompok',
            'selling_modes' => ['batch'],
            'purposes' => ['pembesaran'],
            'form_fields' => [],
            'is_active' => true,
        ]);
    }

    public function test_silsilah_boleh_dikosongkan_untuk_ternak_individual(): void
    {
        $store = $this->activeStore();

        $this->actingAs($store->owner)
            ->post(route('seller.ternak.store'), $this->individualData())
            ->assertRedirect(route('seller.ternak.index'))
            ->assertSessionHas('success', 'Data ternak berhasil ditambahkan.');

        $this->assertDatabaseHas('livestock', [
            'store_id' => $store->id,
            'commodity_type_id' => $this->individualType->id,
            'nama_induk_betina' => null,
            'ras_induk_betina' => null,
            'nama_pejantan' => null,
            'ras_pejantan' => null,
        ]);
    }

    public function test_silsilah_yang_diisi_sebagian_ditolak_dan_isian_lama_dipertahankan(): void
    {
        $store = $this->activeStore();
        $data = $this->individualData([
            'nama_induk_betina' => 'Ratu',
        ]);

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.create'))
            ->post(route('seller.ternak.store'), $data)
            ->assertRedirect(route('seller.ternak.create'))
            ->assertSessionHasErrors([
                'ras_induk_betina',
                'nama_pejantan',
                'ras_pejantan',
            ])
            ->assertSessionHas('_old_input.nama_induk_betina', 'Ratu');

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.create'))
            ->assertOk()
            ->assertSee('value="Ratu"', false);
    }

    public function test_silsilah_lengkap_tersimpan_pada_profil_ternak(): void
    {
        $store = $this->activeStore();

        $this->actingAs($store->owner)
            ->post(route('seller.ternak.store'), $this->individualData([
                'nama_induk_betina' => 'Ratu',
                'ras_induk_betina' => 'Limousin',
                'nama_pejantan' => 'Bima Sakti',
                'ras_pejantan' => 'Limousin',
            ]))
            ->assertRedirect(route('seller.ternak.index'));

        $this->assertDatabaseHas('livestock', [
            'store_id' => $store->id,
            'nama_induk_betina' => 'Ratu',
            'ras_induk_betina' => 'Limousin',
            'nama_pejantan' => 'Bima Sakti',
            'ras_pejantan' => 'Limousin',
        ]);
    }

    public function test_form_batch_menyembunyikan_dan_tidak_menyimpan_silsilah(): void
    {
        $store = $this->activeStore();
        $livestock = $this->createBatchLivestock($store);

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.edit', $livestock))
            ->assertOk()
            ->assertSee('id="pedigree-card" class="livestock-form-card space-y-4 hidden"', false)
            ->assertSeeText('Nama induk betina');

        $data = $this->individualData([
            'commodity_type_id' => $this->batchType->id,
            'mode' => 'batch',
            'purpose' => 'pembesaran',
            'nama_induk_betina' => 'Tidak boleh tersimpan',
            'ras_induk_betina' => 'Limousin',
            'nama_pejantan' => 'Bima Sakti',
            'ras_pejantan' => 'Limousin',
        ]);
        unset($data['umur'], $data['berat']);
        $data['jumlah'] = 3;
        $data['stok'] = 3;

        $this->actingAs($store->owner)
            ->post(route('seller.ternak.store'), $data)
            ->assertRedirect(route('seller.ternak.index'));

        $this->assertDatabaseHas('livestock', [
            'store_id' => $store->id,
            'commodity_type_id' => $this->batchType->id,
            'nama_induk_betina' => null,
            'ras_induk_betina' => null,
            'nama_pejantan' => null,
            'ras_pejantan' => null,
        ]);
    }

    public function test_detail_ternak_menampilkan_silsilah_dan_terhubung_dari_daftar(): void
    {
        $store = $this->activeStore();
        $livestock = $this->createIndividualLivestock($store, [
            'nama_induk_betina' => 'Ratu',
            'ras_induk_betina' => 'Limousin',
            'nama_pejantan' => 'Bima Sakti',
            'ras_pejantan' => 'Limousin',
        ]);

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.index'))
            ->assertOk()
            ->assertSee(route('seller.ternak.show', $livestock));

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.show', $livestock))
            ->assertOk()
            ->assertSeeText('Silsilah')
            ->assertSeeText('Ratu')
            ->assertSeeText('Bima Sakti')
            ->assertSeeText('Limousin');
    }

    public function test_toko_lain_tidak_dapat_melihat_detail_ternak(): void
    {
        $ownerStore = $this->activeStore();
        $otherStore = $this->activeStore();
        $livestock = $this->createIndividualLivestock($otherStore);

        $this->actingAs($ownerStore->owner)
            ->get(route('seller.ternak.show', $livestock))
            ->assertNotFound();
    }

    private function activeStore(): Store
    {
        $owner = User::factory()->create(['role' => 'peternak']);

        return Store::query()->create([
            'user_id' => $owner->id,
            'name' => 'Peternakan Sumber Rejeki',
            'business_categories' => ['ternak_besar'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Glagah, Banyuwangi',
            'status' => StoreStatus::Active,
        ]);
    }

    private function createIndividualLivestock(Store $store, array $overrides = []): Livestock
    {
        return Livestock::query()->create(array_replace($this->individualData(), [
            'store_id' => $store->id,
        ], $overrides));
    }

    private function createBatchLivestock(Store $store): Livestock
    {
        return Livestock::query()->create([
            'store_id' => $store->id,
            'commodity_type_id' => $this->batchType->id,
            'mode' => 'batch',
            'purpose' => 'pembesaran',
            'jumlah' => 3,
            'harga' => 12500000,
            'satuan_jual' => 'ekor',
            'stok' => 3,
            'status' => 'aktif',
        ]);
    }

    private function individualData(array $overrides = []): array
    {
        return array_replace([
            'category_id' => $this->category->id,
            'commodity_type_id' => $this->individualType->id,
            'mode' => 'individual',
            'purpose' => 'bibit',
            'nama_kode' => 'Sapi individual',
            'ras' => 'Limousin',
            'jenis_kelamin' => 'betina',
            'umur' => 12,
            'berat' => 150,
            'jumlah' => null,
            'harga' => 12500000,
            'satuan_jual' => 'ekor',
            'stok' => 1,
            'status' => 'aktif',
            'nama_induk_betina' => null,
            'ras_induk_betina' => null,
            'nama_pejantan' => null,
            'ras_pejantan' => null,
        ], $overrides);
    }
}
