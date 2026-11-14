<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\LivestockProduct;
use App\Models\LivestockProductMedia;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LivestockProductTest extends TestCase
{
    use RefreshDatabase;

    private CommodityCategory $category;

    private CommodityType $productType;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CommodityCategory::query()->create([
            'group' => 'hasil_ternak',
            'name' => 'Produk Susu',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->productType = $this->makeType($this->category, 'Susu Sapi Segar');
        $this->store = $this->activeStore();
    }

    public function test_peternak_dapat_menambah_produk_dan_foto(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->store->owner)
            ->post(route('seller.hasil-ternak.store'), $this->validData([
                'media' => [UploadedFile::fake()->image('susu.png')],
            ]));

        $response->assertRedirect(route('seller.hasil-ternak.index'))
            ->assertSessionHas('success', 'Produk hasil ternak berhasil ditambahkan.');
        $product = LivestockProduct::query()->firstOrFail();
        $this->assertDatabaseHas('livestock_products', [
            'store_id' => $this->store->id,
            'commodity_type_id' => $this->productType->id,
            'stok' => 12.5,
            'satuan' => 'liter',
            'grade' => 'A',
            'penyimpanan_dingin' => true,
        ]);
        $this->assertDatabaseCount('livestock_product_media', 1);
        Storage::disk('public')->assertExists($product->media()->firstOrFail()->path);
    }

    public function test_tanggal_kedaluwarsa_yang_sudah_lewat_atau_sebelum_produksi_ditolak(): void
    {
        $this->actingAs($this->store->owner)
            ->from(route('seller.hasil-ternak.create'))
            ->post(route('seller.hasil-ternak.store'), $this->validData([
                'tanggal_kedaluwarsa' => today()->subDay()->toDateString(),
            ]))
            ->assertRedirect(route('seller.hasil-ternak.create'))
            ->assertSessionHasErrors('tanggal_kedaluwarsa');

        $this->actingAs($this->store->owner)
            ->from(route('seller.hasil-ternak.create'))
            ->post(route('seller.hasil-ternak.store'), $this->validData([
                'tanggal_produksi' => today()->addDays(2)->toDateString(),
                'tanggal_kedaluwarsa' => today()->addDay()->toDateString(),
            ]))
            ->assertRedirect(route('seller.hasil-ternak.create'))
            ->assertSessionHasErrors('tanggal_kedaluwarsa');

        $this->assertDatabaseCount('livestock_products', 0);
    }

    public function test_status_produk_dihitung_dengan_prioritas_tanggal_lalu_stok(): void
    {
        $available = $this->createProduct(['tanggal_kedaluwarsa' => today()->addDays(4)]);
        $almostExpired = $this->createProduct(['tanggal_kedaluwarsa' => today()->addDays(3)]);
        $outOfStock = $this->createProduct([
            'stok' => 0,
            'tanggal_kedaluwarsa' => today()->addDays(8),
        ]);
        $expired = $this->createProduct([
            'stok' => 0,
            'tanggal_kedaluwarsa' => today()->subDay(),
        ]);

        $this->assertSame('tampil', $available->status);
        $this->assertSame('hampir_kedaluwarsa', $almostExpired->status);
        $this->assertSame('habis', $outOfStock->status);
        $this->assertSame('kedaluwarsa', $expired->status);
    }

    public function test_restock_menambahkan_stok_dan_menolak_produk_kedaluwarsa(): void
    {
        $product = $this->createProduct(['stok' => 2.5]);

        $this->actingAs($this->store->owner)
            ->post(route('seller.hasil-ternak.restock', $product), ['stok' => '1.25'])
            ->assertRedirect(route('seller.hasil-ternak.index'))
            ->assertSessionHas('success');

        $this->assertSame('3.750', $product->fresh()->stok);

        $expiredProduct = $this->createProduct([
            'stok' => '1',
            'tanggal_kedaluwarsa' => today()->subDay(),
        ]);
        $this->actingAs($this->store->owner)
            ->from(route('seller.hasil-ternak.restock.form', $expiredProduct))
            ->post(route('seller.hasil-ternak.restock', $expiredProduct), ['stok' => '2'])
            ->assertRedirect(route('seller.hasil-ternak.restock.form', $expiredProduct))
            ->assertSessionHasErrors('stok');

        $this->assertSame('1.000', $expiredProduct->fresh()->stok);
    }

    public function test_foto_produk_harus_jpg_png_atau_webp_dan_maksimal_10_mb(): void
    {
        Storage::fake('public');

        $this->actingAs($this->store->owner)
            ->from(route('seller.hasil-ternak.create'))
            ->post(route('seller.hasil-ternak.store'), $this->validData([
                'media' => [UploadedFile::fake()->create('catatan.txt', 10, 'text/plain')],
            ]))
            ->assertRedirect(route('seller.hasil-ternak.create'))
            ->assertSessionHasErrors('media.0');

        $this->actingAs($this->store->owner)
            ->from(route('seller.hasil-ternak.create'))
            ->post(route('seller.hasil-ternak.store'), $this->validData([
                'media' => [UploadedFile::fake()->image('besar.jpg')->size(10241)],
            ]))
            ->assertRedirect(route('seller.hasil-ternak.create'))
            ->assertSessionHasErrors('media.0');

        $this->assertDatabaseCount('livestock_products', 0);
    }

    public function test_pemilik_dapat_mengubah_menghapus_produk_dan_fotonya(): void
    {
        Storage::fake('public');
        $product = $this->createProduct();
        $oldPhoto = $this->attachPhoto($product, 'old.jpg');
        Storage::disk('public')->put($oldPhoto->path, 'old photo');

        $this->actingAs($this->store->owner)
            ->put(route('seller.hasil-ternak.update', $product), $this->validData([
                'stok' => '24.5',
                'media_remove' => [$oldPhoto->id],
                'media' => [UploadedFile::fake()->image('new.jpg')],
            ]))
            ->assertRedirect(route('seller.hasil-ternak.index'))
            ->assertSessionHas('success');

        $this->assertSame('24.500', $product->fresh()->stok);
        $this->assertDatabaseMissing('livestock_product_media', ['id' => $oldPhoto->id]);
        Storage::disk('public')->assertMissing($oldPhoto->path);
        $newPhoto = $product->media()->firstOrFail();
        Storage::disk('public')->assertExists($newPhoto->path);

        $this->actingAs($this->store->owner)
            ->delete(route('seller.hasil-ternak.destroy', $product))
            ->assertRedirect(route('seller.hasil-ternak.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('livestock_products', ['id' => $product->id]);
        $this->assertDatabaseMissing('livestock_product_media', ['id' => $newPhoto->id]);
        Storage::disk('public')->assertMissing($newPhoto->path);
    }

    public function test_scope_visible_in_catalog_hanya_memuat_produk_berstok_dan_belum_kedaluwarsa(): void
    {
        $visible = $this->createProduct(['tanggal_kedaluwarsa' => today()]);
        $this->createProduct(['stok' => 0]);
        $this->createProduct(['tanggal_kedaluwarsa' => today()->subDay()]);

        $this->assertSame([$visible->id], LivestockProduct::visibleInCatalog()->pluck('id')->all());
    }

    public function test_halaman_daftar_form_ubah_dan_restock_dapat_dibuka_pemiliknya(): void
    {
        $product = $this->createProduct();

        $this->actingAs($this->store->owner)
            ->get(route('seller.hasil-ternak.index'))
            ->assertOk()
            ->assertSeeText('Hasil ternak')
            ->assertSeeText('Susu Sapi Segar')
            ->assertSeeText('Tampil di katalog');

        $this->actingAs($this->store->owner)
            ->get(route('seller.hasil-ternak.create'))
            ->assertOk()
            ->assertSee('name="media[]"', false);
        $this->actingAs($this->store->owner)
            ->get(route('seller.hasil-ternak.edit', $product))
            ->assertOk()
            ->assertSee('name="tanggal_kedaluwarsa"', false);
        $this->actingAs($this->store->owner)
            ->get(route('seller.hasil-ternak.restock.form', $product))
            ->assertOk()
            ->assertSee('name="stok"', false);
    }

    public function test_hanya_pemilik_toko_dapat_melihat_mengubah_menghapus_atau_restock(): void
    {
        $otherStore = $this->activeStore();
        $product = $this->createProduct();

        $this->actingAs($otherStore->owner)
            ->get(route('seller.hasil-ternak.edit', $product))
            ->assertNotFound();
        $this->actingAs($otherStore->owner)
            ->get(route('seller.hasil-ternak.restock.form', $product))
            ->assertNotFound();
        $this->actingAs($otherStore->owner)
            ->put(route('seller.hasil-ternak.update', $product), $this->validData())
            ->assertNotFound();
        $this->actingAs($otherStore->owner)
            ->post(route('seller.hasil-ternak.restock', $product), ['stok' => '1'])
            ->assertNotFound();
        $this->actingAs($otherStore->owner)
            ->delete(route('seller.hasil-ternak.destroy', $product))
            ->assertNotFound();

        $this->assertDatabaseHas('livestock_products', ['id' => $product->id, 'store_id' => $this->store->id]);
    }

    public function test_form_hanya_menampilkan_jenis_produk_hasil_ternak_aktif(): void
    {
        $livestockCategory = CommodityCategory::query()->create([
            'group' => 'ternak_hidup',
            'name' => 'Ternak Unggas',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $this->makeType($livestockCategory, 'Ayam Broiler');
        $inactiveType = $this->makeType($this->category, 'Susu Nonaktif', false);
        $inactiveCategory = CommodityCategory::query()->create([
            'group' => 'hasil_ternak',
            'name' => 'Produk Nonaktif',
            'sort_order' => 3,
            'is_active' => false,
        ]);
        $this->makeType($inactiveCategory, 'Keju Nonaktif');

        $this->actingAs($this->store->owner)
            ->get(route('seller.hasil-ternak.create'))
            ->assertOk()
            ->assertSeeText('Susu Sapi Segar')
            ->assertDontSeeText('Ayam Broiler')
            ->assertDontSeeText('Susu Nonaktif')
            ->assertDontSeeText('Keju Nonaktif');

        $this->actingAs($this->store->owner)
            ->post(route('seller.hasil-ternak.store'), $this->validData([
                'commodity_type_id' => $inactiveType->id,
            ]))
            ->assertSessionHasErrors('commodity_type_id');

        $this->assertDatabaseCount('livestock_products', 0);
    }

    private function activeStore(): Store
    {
        $owner = User::factory()->create(['role' => 'peternak']);

        return Store::query()->create([
            'user_id' => $owner->id,
            'name' => 'Toko Hasil Ternak '.$owner->id,
            'business_categories' => ['hasil_ternak'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Glagah, Banyuwangi',
            'status' => StoreStatus::Active,
        ]);
    }

    private function makeType(CommodityCategory $category, string $name, bool $isActive = true): CommodityType
    {
        return CommodityType::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'selling_modes' => ['produk'],
            'purposes' => [],
            'form_fields' => [],
            'is_active' => $isActive,
        ]);
    }

    private function createProduct(array $overrides = []): LivestockProduct
    {
        return LivestockProduct::query()->create($this->validData($overrides) + [
            'store_id' => $this->store->id,
        ]);
    }

    private function attachPhoto(LivestockProduct $product, string $name): LivestockProductMedia
    {
        return $product->media()->create([
            'path' => 'livestock-products/'.$this->store->id.'/'.$product->id.'/'.$name,
            'original_name' => $name,
            'mime_type' => 'image/jpeg',
            'size' => 12,
        ]);
    }

    private function validData(array $overrides = []): array
    {
        return array_replace([
            'commodity_type_id' => $this->productType->id,
            'stok' => '12.5',
            'satuan' => 'liter',
            'harga' => '18000',
            'tanggal_produksi' => today()->subDay()->toDateString(),
            'tanggal_kedaluwarsa' => today()->addDays(7)->toDateString(),
            'grade' => 'A',
            'penyimpanan_dingin' => '1',
            'asal_ternak' => 'Sapi Perah FH',
        ], $overrides);
    }
}
