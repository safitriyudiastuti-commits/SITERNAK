<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreApplication;
use App\Models\StoreDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_store_owner_sees_verification_details_and_private_documents(): void
    {
        Storage::fake('local');
        [$owner, $store] = $this->createActiveStore();
        $this->createActiveApplication($owner);
        $document = $store->documents()->create([
            'type' => StoreDocument::TYPE_KTP,
            'path' => 'store-documents/'.$store->id.'/ktp.jpg',
            'original_name' => 'ktp.jpg',
        ]);
        Storage::disk('local')->put($document->path, 'private KTP file');

        $this->actingAs($owner)
            ->get(route('seller.toko.verifikasi'))
            ->assertOk()
            ->assertSeeText('Status verifikasi')
            ->assertSeeText('Aktif')
            ->assertSeeText('Disetujui 15 Januari 2026 oleh Admin Pusat')
            ->assertSeeText('Peternakan Sumber Rejeki')
            ->assertSeeText('Ternak besar & Hasil ternak')
            ->assertSeeText('Tidak tersedia pada data toko')
            ->assertSeeText('Slamet Riyadi')
            ->assertSeeText('Dusun Krajan, Glagah, Banyuwangi')
            ->assertSeeText('13 Januari 2026')
            ->assertSeeText('Bank Mandiri · 1410012345678')
            ->assertSeeText('Ganti Rekening')
            ->assertSee('disabled aria-disabled="true"', false)
            ->assertSeeText('Perubahan data toko akan diverifikasi ulang oleh admin (maks 2x24 jam kerja).')
            ->assertSee(route('seller.toko.verifikasi.dokumen', $document), false);

        $this->actingAs($owner)
            ->get(route('seller.toko.verifikasi.dokumen', $document))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=ktp.jpg')
            ->assertHeader('cache-control', 'no-store, private');
    }

    public function test_verification_page_does_not_show_another_users_store_data(): void
    {
        [$owner] = $this->createActiveStore();
        [$otherOwner] = $this->createActiveStore('Pemilik Rahasia', 'Toko Rahasia');
        $this->createActiveApplication($otherOwner, 'Toko Rahasia', 'Rekening Rahasia');

        $this->actingAs($owner)
            ->get(route('seller.toko.verifikasi'))
            ->assertOk()
            ->assertDontSeeText('Toko Rahasia')
            ->assertDontSeeText('Pemilik Rahasia')
            ->assertDontSeeText('Rekening Rahasia');
    }

    public function test_owner_cannot_download_another_store_document(): void
    {
        [$owner] = $this->createActiveStore();
        [, $otherStore] = $this->createActiveStore('Pemilik Lain', 'Toko Lain');
        $document = $otherStore->documents()->create([
            'type' => StoreDocument::TYPE_KTP,
            'path' => 'store-documents/'.$otherStore->id.'/ktp.jpg',
            'original_name' => 'ktp.jpg',
        ]);

        $this->actingAs($owner)
            ->get(route('seller.toko.verifikasi.dokumen', $document))
            ->assertNotFound();
    }

    /**
     * @return array{User, Store}
     */
    private function createActiveStore(
        string $ownerName = 'Slamet Riyadi',
        string $storeName = 'Peternakan Sumber Rejeki',
    ): array {
        $owner = User::factory()->create(['name' => $ownerName, 'role' => 'peternak']);
        $verifier = User::factory()->create(['name' => 'Admin Pusat', 'role' => 'admin']);
        $store = Store::query()->create([
            'user_id' => $owner->id,
            'name' => $storeName,
            'business_categories' => ['ternak_besar', 'hasil_ternak'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Dusun Krajan, Glagah, Banyuwangi',
            'status' => StoreStatus::Active,
            'submitted_at' => '2026-01-13 09:00:00',
            'verified_at' => '2026-01-15 10:00:00',
            'verified_by' => $verifier->id,
        ]);

        return [$owner, $store];
    }

    private function createActiveApplication(
        User $owner,
        string $storeName = 'Peternakan Sumber Rejeki',
        string $bankName = 'Bank Mandiri',
    ): void {
        StoreApplication::query()->create([
            'user_id' => $owner->id,
            'nama_toko' => $storeName,
            'nama_pemilik' => $owner->name,
            'kategori_usaha' => ['ternak_besar', 'hasil_ternak'],
            'kota_kabupaten' => 'Banyuwangi',
            'provinsi' => 'Jawa Timur',
            'alamat' => 'Dusun Krajan, Glagah, Banyuwangi',
            'foto_ktp_path' => 'store-documents/ktp.jpg',
            'foto_lokasi_path' => 'store-documents/lokasi.jpg',
            'buku_rekening_path' => 'store-documents/rekening.jpg',
            'bank_nama' => $bankName,
            'bank_no_rekening' => '1410012345678',
            'bank_atas_nama' => $owner->name,
            'status' => StoreStatus::Active->value,
            'verified_by' => Store::query()->where('user_id', $owner->id)->value('verified_by'),
            'verified_at' => '2026-01-15 10:00:00',
            'created_at' => '2026-01-13 09:00:00',
            'updated_at' => '2026-01-15 10:00:00',
        ]);
    }
}
