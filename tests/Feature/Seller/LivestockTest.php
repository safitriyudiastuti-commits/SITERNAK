<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\LivestockMedia;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LivestockTest extends TestCase
{
    use RefreshDatabase;

    private CommodityCategory $category;

    private CommodityType $batchType;

    private CommodityType $individualType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CommodityCategory::query()->create([
            'group' => 'ternak_hidup',
            'name' => 'Ternak besar',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->batchType = CommodityType::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Sapi Limousin',
            'selling_modes' => ['batch'],
            'purposes' => ['pembesaran', 'siap_potong'],
            'form_fields' => [
                'nama_kode' => false,
                'ras' => false,
                'jenis_kelamin' => true,
                'umur_bulan' => true,
                'berat_kg' => true,
            ],
            'is_active' => true,
        ]);
        $this->individualType = CommodityType::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Sapi Brahman',
            'selling_modes' => ['individual'],
            'purposes' => ['bibit'],
            'form_fields' => CommodityType::DEFAULT_FORM_FIELDS,
            'is_active' => true,
        ]);
    }

    public function test_peternak_dapat_menambah_ternak_dan_menyimpan_medianya(): void
    {
        Storage::fake('public');
        $store = $this->activeStore();

        $response = $this->actingAs($store->owner)->post(route('seller.ternak.store'), $this->validData([
            'media' => [UploadedFile::fake()->image('limousin.jpg')],
        ]));

        $response->assertRedirect(route('seller.ternak.index'))
            ->assertSessionHas('success', 'Data ternak berhasil ditambahkan.');
        $this->assertDatabaseHas('livestock', [
            'store_id' => $store->id,
            'commodity_type_id' => $this->batchType->id,
            'mode' => 'batch',
            'jumlah' => 3,
            'umur' => 12,
            'berat' => 150.5,
        ]);
        $this->assertDatabaseCount('livestock_media', 1);
        Storage::disk('public')->assertExists(LivestockMedia::query()->firstOrFail()->path);
    }

    public function test_form_mengirim_nama_field_yang_diterima_request(): void
    {
        Storage::fake('public');
        $store = $this->activeStore();
        $data = $this->validData([
            'mode' => 'batch',
            'purpose' => 'siap_potong',
            'jumlah' => 4,
            'stok' => 4,
            'media' => [UploadedFile::fake()->image('sapi.jpg')],
        ]);

        $this->actingAs($store->owner)
            ->withSession(['_old_input' => [
                'category_id' => $this->category->id,
                'commodity_type_id' => $this->batchType->id,
                'mode' => 'batch',
                'purpose' => 'siap_potong',
            ]])
            ->get(route('seller.ternak.create'))
            ->assertOk()
            ->assertSee('name="mode"', false)
            ->assertSee('name="purpose"', false)
            ->assertSee('name="stok"', false)
            ->assertSee('name="media[]"', false);

        $this->actingAs($store->owner)
            ->post(route('seller.ternak.store'), $data)
            ->assertRedirect(route('seller.ternak.index'))
            ->assertSessionHas('success', 'Data ternak berhasil ditambahkan.');

        $this->assertDatabaseHas('livestock', [
            'store_id' => $store->id,
            'mode' => 'batch',
            'purpose' => 'siap_potong',
            'jumlah' => 4,
            'stok' => 4,
        ]);

        $individualData = $this->validData([
            'commodity_type_id' => $this->individualType->id,
            'mode' => 'individual',
            'purpose' => 'bibit',
            'nama_kode' => 'Sapi Brahman',
            'jumlah' => null,
            'stok' => 1,
        ]);
        $this->actingAs($store->owner)
            ->post(route('seller.ternak.store'), $individualData)
            ->assertRedirect(route('seller.ternak.index'))
            ->assertSessionHas('success', 'Data ternak berhasil ditambahkan.');

        $this->assertDatabaseHas('livestock', [
            'store_id' => $store->id,
            'commodity_type_id' => $this->individualType->id,
            'mode' => 'individual',
            'purpose' => 'bibit',
            'stok' => 1,
        ]);
        $this->assertDatabaseCount('livestock_media', 1);
    }

    public function test_peternak_dapat_mengubah_ternak_milik_tokonya(): void
    {
        $store = $this->activeStore();
        $item = $this->createLivestock($store);

        $this->actingAs($store->owner)
            ->put(route('seller.ternak.update', $item), $this->validData([
                'jumlah' => 5,
                'stok' => 4,
                'harga' => 17500000,
                'status' => 'nonaktif',
            ]))
            ->assertRedirect(route('seller.ternak.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('livestock', [
            'id' => $item->id,
            'jumlah' => 5,
            'stok' => 4,
            'harga' => 17500000,
            'status' => 'nonaktif',
        ]);
    }

    public function test_peternak_dapat_menghapus_ternak_dan_medianya(): void
    {
        Storage::fake('public');
        $store = $this->activeStore();
        $item = $this->createLivestock($store);
        $path = UploadedFile::fake()->image('ternak.jpg')->store("livestock/{$store->id}/{$item->id}", 'public');
        $item->media()->create([
            'path' => $path,
            'original_name' => 'ternak.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'type' => 'image',
        ]);

        $this->actingAs($store->owner)
            ->delete(route('seller.ternak.destroy', $item))
            ->assertRedirect(route('seller.ternak.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('livestock', ['id' => $item->id]);
        $this->assertDatabaseMissing('livestock_media', ['livestock_id' => $item->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_daftar_ternak_dikelompokkan_dengan_kategori_dan_media(): void
    {
        Storage::fake('public');
        $store = $this->activeStore();
        $item = $this->createLivestock($store);
        $path = UploadedFile::fake()->image('limousin.jpg')->store("livestock/{$store->id}/{$item->id}", 'public');
        $item->media()->create([
            'path' => $path,
            'original_name' => 'limousin.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'type' => 'image',
        ]);

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.index'))
            ->assertOk()
            ->assertSeeText('Ternak besar')
            ->assertSeeText('Sapi kelompok A')
            ->assertSeeText('Sapi Limousin')
            ->assertSeeText('Rp 12.500.000')
            ->assertSee('limousin.jpg');
    }

    public function test_validasi_mengikuti_field_wajib_dan_mode_pada_jenis_ternak(): void
    {
        $store = $this->activeStore();
        $invalid = $this->validData([
            'jenis_kelamin' => '',
            'jumlah' => null,
        ]);

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.create'))
            ->post(route('seller.ternak.store'), $invalid)
            ->assertRedirect(route('seller.ternak.create'))
            ->assertSessionHasErrors(['jenis_kelamin', 'jumlah'])
            ->assertSessionHas('_old_input.harga', '12500000');

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.create'))
            ->post(route('seller.ternak.store'), $this->validData(['mode' => 'individual']))
            ->assertRedirect(route('seller.ternak.create'))
            ->assertSessionHasErrors('mode');

        $this->assertDatabaseCount('livestock', 0);
    }

    public function test_validasi_membatasi_jumlah_media_dan_ukuran_setiap_berkas(): void
    {
        $store = $this->activeStore();
        $sixFiles = [];
        for ($index = 1; $index <= 6; $index++) {
            $sixFiles[] = UploadedFile::fake()->image("ternak-{$index}.jpg");
        }

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.create'))
            ->post(route('seller.ternak.store'), $this->validData(['media' => $sixFiles]))
            ->assertRedirect(route('seller.ternak.create'))
            ->assertSessionHasErrors('media');

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.create'))
            ->post(route('seller.ternak.store'), $this->validData([
                'media' => [UploadedFile::fake()->create('terlalu-besar.jpg', 10241, 'image/jpeg')],
            ]))
            ->assertRedirect(route('seller.ternak.create'))
            ->assertSessionHasErrors('media.0');

        $this->assertDatabaseCount('livestock', 0);
    }

    public function test_peternak_tidak_dapat_melihat_mengubah_atau_menghapus_ternak_toko_lain(): void
    {
        $ownerStore = $this->activeStore();
        $otherStore = $this->activeStore();
        $item = $this->createLivestock($otherStore);

        $this->actingAs($ownerStore->owner)
            ->get(route('seller.ternak.edit', $item))
            ->assertNotFound();

        $this->actingAs($ownerStore->owner)
            ->put(route('seller.ternak.update', $item), $this->validData(['jumlah' => 8]))
            ->assertNotFound();

        $this->actingAs($ownerStore->owner)
            ->delete(route('seller.ternak.destroy', $item))
            ->assertNotFound();

        $this->assertDatabaseHas('livestock', ['id' => $item->id, 'jumlah' => 3]);
    }

    public function test_form_hanya_menampilkan_kategori_dan_jenis_aktif_dari_group_ternak_hidup(): void
    {
        $store = $this->activeStore();
        CommodityCategory::query()->create([
            'group' => 'hasil_ternak',
            'name' => 'Hasil ternak',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        CommodityType::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Jenis nonaktif',
            'selling_modes' => ['batch'],
            'purposes' => [],
            'form_fields' => [],
            'is_active' => false,
        ]);

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.create'))
            ->assertOk()
            ->assertSeeText('Ternak besar')
            ->assertSeeText('Sapi Limousin')
            ->assertDontSeeText('Hasil ternak')
            ->assertDontSeeText('Jenis nonaktif');
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

    private function createLivestock(Store $store): Livestock
    {
        $attributes = $this->validData([
            'store_id' => $store->id,
        ]);
        unset($attributes['category_id']);

        return Livestock::query()->create($attributes);
    }

    private function validData(array $overrides = []): array
    {
        return array_replace([
            'category_id' => $this->category->id,
            'commodity_type_id' => $this->batchType->id,
            'mode' => 'batch',
            'purpose' => 'pembesaran',
            'nama_kode' => 'Sapi kelompok A',
            'ras' => 'Limousin',
            'jenis_kelamin' => 'jantan',
            'umur' => 12,
            'berat' => 150.5,
            'jumlah' => 3,
            'harga' => 12500000,
            'satuan_jual' => 'ekor',
            'stok' => 3,
            'status' => 'aktif',
        ], $overrides);
    }
}
