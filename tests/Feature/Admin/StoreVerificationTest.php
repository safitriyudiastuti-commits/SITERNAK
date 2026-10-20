<?php

namespace Tests\Feature\Admin;

use App\Enums\StoreStatus;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Notifications\StoreVerificationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pendingStore(): Store
    {
        $owner = User::factory()->create(['role' => 'pembeli']);

        return Store::create([
            'user_id' => $owner->id,
            'name' => 'Peternakan Sumber Rejeki',
            'business_categories' => ['ternak_besar', 'hasil_ternak'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Dusun Krajan, Glagah, Banyuwangi',
            'status' => StoreStatus::PendingVerification,
            'submitted_at' => now()->subHours(10),
        ]);
    }

    private function allChecks(): array
    {
        return ['check_ktp' => 1, 'check_location' => 1, 'check_bankbook' => 1];
    }

    public function test_admin_bisa_melihat_daftar_dan_detail_pengajuan(): void
    {
        $store = $this->pendingStore();

        $this->actingAs($this->admin())->get(route('admin.verifikasi-toko.index'))
            ->assertOk()->assertSee('Verifikasi peternak')->assertSee('Peternakan Sumber Rejeki');

        $this->actingAs($this->admin())->get(route('admin.verifikasi-toko.show', $store))
            ->assertOk()->assertSee('Setujui Peternak');
    }

    public function test_pengguna_bukan_admin_ditolak(): void
    {
        $store = $this->pendingStore();
        $pembeli = User::factory()->create(['role' => 'pembeli']);

        $this->actingAs($pembeli)->get(route('admin.verifikasi-toko.index'))->assertForbidden();
        $this->actingAs($pembeli)->post(route('admin.verifikasi-toko.verify', $store), ['decision' => 'approve'] + $this->allChecks())
            ->assertForbidden();
    }

    public function test_setujui_mengaktifkan_toko_dan_mencatat_audit_log(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $store = $this->pendingStore();

        $this->actingAs($admin)
            ->post(route('admin.verifikasi-toko.verify', $store), ['decision' => 'approve'] + $this->allChecks())
            ->assertRedirect(route('admin.verifikasi-toko.show', $store));

        $store->refresh();
        $this->assertSame(StoreStatus::Active, $store->status);
        $this->assertSame($admin->id, $store->verified_by);
        $this->assertNotNull($store->verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'store.approved', 'actor_id' => $admin->id, 'subject_id' => $store->id]);
        Notification::assertSentTo($store->owner, StoreVerificationResult::class);
    }

    public function test_setujui_wajib_mencentang_tiga_poin_pemeriksaan(): void
    {
        $store = $this->pendingStore();

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi-toko.verify', $store), ['decision' => 'approve', 'check_ktp' => 1])
            ->assertSessionHasErrors(['check_location', 'check_bankbook']);

        $this->assertSame(StoreStatus::PendingVerification, $store->fresh()->status);
    }

    public function test_tolak_wajib_alasan(): void
    {
        $store = $this->pendingStore();

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi-toko.verify', $store), ['decision' => 'reject'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(StoreStatus::PendingVerification, $store->fresh()->status);
    }

    public function test_tolak_menyimpan_alasan_dan_mencatat_audit_log(): void
    {
        Notification::fake();
        $store = $this->pendingStore();
        $reason = 'Foto KTP kurang jelas dan nama tidak terbaca.';

        $this->actingAs($this->admin())
            ->post(route('admin.verifikasi-toko.verify', $store), ['decision' => 'reject', 'rejection_reason' => $reason])
            ->assertRedirect(route('admin.verifikasi-toko.show', $store));

        $store->refresh();
        $this->assertSame(StoreStatus::Rejected, $store->status);
        $this->assertSame($reason, $store->rejection_reason);
        $this->assertTrue(AuditLog::where('action', 'store.rejected')->exists());
        Notification::assertSentTo($store->owner, StoreVerificationResult::class);
    }

    public function test_pengajuan_yang_sudah_diputuskan_tidak_bisa_diputuskan_lagi(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $store = $this->pendingStore();
        $payload = ['decision' => 'approve'] + $this->allChecks();

        $this->actingAs($admin)->post(route('admin.verifikasi-toko.verify', $store), $payload);
        $this->actingAs($admin)->post(route('admin.verifikasi-toko.verify', $store), ['decision' => 'reject', 'rejection_reason' => 'Coba tolak setelah disetujui'])
            ->assertSessionHas('error');

        $this->assertSame(StoreStatus::Active, $store->fresh()->status);
        $this->assertSame(1, AuditLog::whereIn('action', ['store.approved', 'store.rejected'])->count());
    }
}
