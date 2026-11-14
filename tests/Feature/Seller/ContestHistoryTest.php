<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\LivestockContestHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContestHistoryTest extends TestCase
{
    use RefreshDatabase;

    private CommodityCategory $category;

    private CommodityType $contestType;

    private CommodityType $noContestType;

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
        $this->contestType = $this->makeType('Sapi Kontes', ['Postur', 'Bobot badan'], ['individual']);
        $this->noContestType = $this->makeType('Sapi Biasa', [], ['individual']);
        $this->batchType = $this->makeType('Sapi Batch Kontes', ['Postur'], ['batch']);
    }

    public function test_peternak_dapat_menambah_riwayat_dan_sertifikat_privat_dengan_status_pending(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        $store = $this->activeStore();
        $livestock = $this->createLivestock($store, $this->contestType);
        $certificate = UploadedFile::fake()->image('sertifikat.jpg');

        $this->actingAs($store->owner)
            ->post(route('seller.ternak.kontes.store', $livestock), $this->validData([
                'certificate' => $certificate,
            ]))
            ->assertRedirect(route('seller.ternak.show', $livestock))
            ->assertSessionHas('success');

        $history = LivestockContestHistory::query()->firstOrFail();
        $this->assertSame('pending', $history->status);
        $this->assertDatabaseHas('livestock_contest_histories', [
            'livestock_id' => $livestock->id,
            'contest_name' => 'Kontes Sapi Jawa Timur 2025',
            'category' => 'Postur',
            'rank' => 'Juara 2',
            'status' => 'pending',
        ]);
        Storage::disk('private')->assertExists($history->certificate_path);
        Storage::disk('public')->assertMissing($history->certificate_path);
    }

    public function test_sertifikat_hanya_menerima_jpg_png_pdf_maksimal_5_mb(): void
    {
        $store = $this->activeStore();
        $livestock = $this->createLivestock($store, $this->contestType);

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.show', $livestock))
            ->post(route('seller.ternak.kontes.store', $livestock), $this->validData([
                'certificate' => UploadedFile::fake()->create('sertifikat.txt', 50, 'text/plain'),
            ]))
            ->assertRedirect(route('seller.ternak.show', $livestock))
            ->assertSessionHasErrors('certificate');

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.show', $livestock))
            ->post(route('seller.ternak.kontes.store', $livestock), $this->validData([
                'certificate' => UploadedFile::fake()->create('sertifikat.pdf', 5121, 'application/pdf'),
            ]))
            ->assertRedirect(route('seller.ternak.show', $livestock))
            ->assertSessionHasErrors('certificate');

        $this->assertDatabaseCount('livestock_contest_histories', 0);
    }

    public function test_kontes_ditolak_untuk_ternak_batch_dan_jenis_tanpa_kategori(): void
    {
        $store = $this->activeStore();
        $batchLivestock = $this->createLivestock($store, $this->batchType, 'batch');
        $ordinaryLivestock = $this->createLivestock($store, $this->noContestType);
        $data = $this->validData([
            'certificate' => UploadedFile::fake()->image('sertifikat.png'),
        ]);

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.show', $batchLivestock))
            ->post(route('seller.ternak.kontes.store', $batchLivestock), $data)
            ->assertRedirect(route('seller.ternak.show', $batchLivestock))
            ->assertSessionHasErrors('contest_name');

        $this->actingAs($store->owner)
            ->from(route('seller.ternak.show', $ordinaryLivestock))
            ->post(route('seller.ternak.kontes.store', $ordinaryLivestock), $data)
            ->assertRedirect(route('seller.ternak.show', $ordinaryLivestock))
            ->assertSessionHasErrors(['contest_name', 'category']);

        $this->assertDatabaseCount('livestock_contest_histories', 0);
        $this->actingAs($store->owner)
            ->get(route('seller.ternak.show', $batchLivestock))
            ->assertDontSeeText('Tambah riwayat kontes');
        $this->actingAs($store->owner)
            ->get(route('seller.ternak.show', $ordinaryLivestock))
            ->assertDontSeeText('Tambah riwayat kontes');
    }

    public function test_lencana_hanya_muncul_untuk_riwayat_terverifikasi(): void
    {
        $store = $this->activeStore();
        $verifiedLivestock = $this->createLivestock($store, $this->contestType);
        $pendingLivestock = $this->createLivestock($store, $this->contestType);
        $this->createHistory($verifiedLivestock, ['status' => 'verified']);
        $this->createHistory($pendingLivestock, ['status' => 'pending']);

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.index'))
            ->assertOk()
            ->assertSeeText('Terverifikasi');

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.show', $verifiedLivestock))
            ->assertOk()
            ->assertSee('<span class="mt-2 inline-flex rounded-full bg-[#e0f3e7] px-2.5 py-1 text-[10px] font-semibold text-[#247341]">Terverifikasi</span>', false);

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.show', $pendingLivestock))
            ->assertOk()
            ->assertSeeText('Menunggu verifikasi')
            ->assertDontSee('<span class="mt-2 inline-flex rounded-full bg-[#e0f3e7] px-2.5 py-1 text-[10px] font-semibold text-[#247341]">Terverifikasi</span>', false);
    }

    public function test_alasan_penolakan_hanya_ditampilkan_di_detail_milik_peternak(): void
    {
        $store = $this->activeStore();
        $otherStore = $this->activeStore();
        $livestock = $this->createLivestock($store, $this->contestType);
        $history = $this->createHistory($livestock, [
            'status' => 'rejected',
            'rejection_reason' => 'Sertifikat tidak terbaca.',
        ]);
        $verifiedHistory = $this->createHistory(
            $this->createLivestock($store, $this->contestType),
            ['status' => 'verified']
        );

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.show', $livestock))
            ->assertOk()
            ->assertSeeText('Sertifikat tidak terbaca.');

        $this->actingAs($otherStore->owner)
            ->get(route('seller.ternak.show', $livestock))
            ->assertNotFound();

        $this->assertSame(
            [$verifiedHistory->id],
            LivestockContestHistory::publicVerified()->pluck('id')->all()
        );
    }

    public function test_pemilik_dapat_mengunduh_sertifikat_dan_toko_lain_ditolak(): void
    {
        Storage::fake('private');
        $store = $this->activeStore();
        $otherStore = $this->activeStore();
        $livestock = $this->createLivestock($store, $this->contestType);
        $history = $this->createHistory($livestock);
        Storage::disk('private')->put($history->certificate_path, 'private certificate');

        $this->actingAs($store->owner)
            ->get(route('seller.ternak.kontes.sertifikat', [$livestock, $history]))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($otherStore->owner)
            ->get(route('seller.ternak.kontes.sertifikat', [$livestock, $history]))
            ->assertNotFound();

        $this->actingAs($otherStore->owner)
            ->delete(route('seller.ternak.kontes.destroy', [$livestock, $history]))
            ->assertNotFound();

        $this->assertDatabaseHas('livestock_contest_histories', ['id' => $history->id]);
    }

    public function test_pemilik_dapat_menghapus_riwayat_dan_sertifikat_privat(): void
    {
        Storage::fake('private');
        $store = $this->activeStore();
        $livestock = $this->createLivestock($store, $this->contestType);
        $history = $this->createHistory($livestock);
        Storage::disk('private')->put($history->certificate_path, 'private certificate');

        $this->actingAs($store->owner)
            ->delete(route('seller.ternak.kontes.destroy', [$livestock, $history]))
            ->assertRedirect(route('seller.ternak.show', $livestock))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('livestock_contest_histories', ['id' => $history->id]);
        Storage::disk('private')->assertMissing($history->certificate_path);
    }

    public function test_menghapus_ternak_juga_menghapus_sertifikat_kontes_privat(): void
    {
        Storage::fake('private');
        $store = $this->activeStore();
        $livestock = $this->createLivestock($store, $this->contestType);
        $history = $this->createHistory($livestock);
        Storage::disk('private')->put($history->certificate_path, 'private certificate');

        $this->actingAs($store->owner)
            ->delete(route('seller.ternak.destroy', $livestock))
            ->assertRedirect(route('seller.ternak.index'));

        $this->assertDatabaseMissing('livestock_contest_histories', ['id' => $history->id]);
        Storage::disk('private')->assertMissing($history->certificate_path);
    }

    private function makeType(string $name, array $contestCategories, array $sellingModes): CommodityType
    {
        return CommodityType::query()->create([
            'category_id' => $this->category->id,
            'name' => $name,
            'selling_modes' => $sellingModes,
            'purposes' => ['indukan_kontes'],
            'form_fields' => CommodityType::DEFAULT_FORM_FIELDS,
            'contest_categories' => $contestCategories ?: null,
            'is_active' => true,
        ]);
    }

    private function activeStore(): Store
    {
        $owner = User::factory()->create(['role' => 'peternak']);

        return Store::query()->create([
            'user_id' => $owner->id,
            'name' => 'Peternakan Kontes',
            'business_categories' => ['ternak_besar'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Glagah, Banyuwangi',
            'status' => StoreStatus::Active,
        ]);
    }

    private function createLivestock(Store $store, CommodityType $type, string $mode = 'individual'): Livestock
    {
        return Livestock::query()->create([
            'store_id' => $store->id,
            'commodity_type_id' => $type->id,
            'mode' => $mode,
            'purpose' => 'indukan_kontes',
            'nama_kode' => 'Sapi Kontes',
            'ras' => 'Limousin',
            'jenis_kelamin' => 'jantan',
            'umur' => 24,
            'berat' => 400,
            'jumlah' => $mode === 'batch' ? 3 : 1,
            'harga' => 30000000,
            'satuan_jual' => 'ekor',
            'stok' => $mode === 'batch' ? 3 : 1,
            'status' => 'aktif',
        ]);
    }

    private function createHistory(Livestock $livestock, array $overrides = []): LivestockContestHistory
    {
        return LivestockContestHistory::query()->create(array_replace([
            'livestock_id' => $livestock->id,
            'contest_name' => 'Kontes Sapi Jawa Timur 2025',
            'category' => 'Postur',
            'rank' => 'Juara 2',
            'contest_date' => '2025-08-14',
            'organizer' => 'Dinas Peternakan Jatim',
            'certificate_path' => "livestock-contests/{$livestock->store_id}/{$livestock->id}/sertifikat.pdf",
            'certificate_original_name' => 'sertifikat.pdf',
            'certificate_mime_type' => 'application/pdf',
            'status' => 'pending',
            'rejection_reason' => null,
        ], $overrides));
    }

    private function validData(array $overrides = []): array
    {
        return array_replace([
            'contest_name' => 'Kontes Sapi Jawa Timur 2025',
            'category' => 'Postur',
            'rank' => 'Juara 2',
            'contest_date' => '2025-08-14',
            'organizer' => 'Dinas Peternakan Jatim',
            'certificate' => UploadedFile::fake()->image('sertifikat.jpg'),
        ], $overrides);
    }
}
