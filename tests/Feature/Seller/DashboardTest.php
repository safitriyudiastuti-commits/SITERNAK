<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('aria-current="page"', false);
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
}
