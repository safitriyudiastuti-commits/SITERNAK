<?php

namespace Tests\Feature\Seller;

use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_sees_only_reviews_for_their_store_and_real_rating_summary(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();

        $this->createReview($store, 5, 'Ulasan bintang lima', 'reviews/photo.jpg');
        $this->createReview($store, 5, 'Ulasan kedua');
        $this->createReview($store, 4, 'Ulasan bintang empat');
        $this->createReview($store, 2, 'Ulasan bintang dua', null, 'Balasan sudah dikirim.');
        $this->createReview($otherStore, 1, 'Ulasan toko lain');

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index'))
            ->assertOk()
            ->assertSeeText('Penilaian toko')
            ->assertSeeText('Lihat dan balas ulasan dari pembeli.')
            ->assertSeeText('Berdasarkan 4 penilaian')
            ->assertSeeText('Ulasan bintang lima')
            ->assertSeeText('Balasan sudah dikirim.')
            ->assertDontSeeText('Ulasan toko lain')
            ->assertSee('aria-current="page"', false);

        $response = $this->actingAs($seller)->get(route('seller.penilaian.index'));
        $this->assertStringContainsString('4,0', $response->getContent());
        $this->assertMatchesRegularExpression('/5 bintang.*?<span[^>]*>2<\/span>/s', $response->getContent());
        $this->assertMatchesRegularExpression('/4 bintang.*?<span[^>]*>1<\/span>/s', $response->getContent());
    }

    public function test_review_tabs_filter_and_preserve_pagination_query(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $five = $this->createReview($store, 5, 'REVIEW-FIVE', 'reviews/five.jpg');
        $this->createReview($store, 5, 'REVIEW-FIVE-2');
        $this->createReview($store, 4, 'REVIEW-FOUR');
        $replied = $this->createReview($store, 3, 'REVIEW-REPLIED');
        $replied->update(['balasan' => 'Sudah dibalas.']);
        $this->createReview($store, 2, 'REVIEW-PENDING');

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index', ['tab' => 'bintang_5']))
            ->assertOk()
            ->assertSeeText('REVIEW-FIVE')
            ->assertSeeText('REVIEW-FIVE-2')
            ->assertDontSeeText('REVIEW-FOUR')
            ->assertSee('aria-current="page"', false);

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index', ['tab' => 'bintang_4']))
            ->assertOk()
            ->assertSeeText('REVIEW-FOUR')
            ->assertDontSeeText('REVIEW-FIVE');

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index', ['tab' => 'dengan_foto']))
            ->assertOk()
            ->assertSeeText('REVIEW-FIVE')
            ->assertDontSeeText('REVIEW-PENDING');

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index', ['tab' => 'belum_dibalas']))
            ->assertOk()
            ->assertSeeText('REVIEW-PENDING')
            ->assertDontSeeText('REVIEW-REPLIED');

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index', ['tab' => 'not-allowed']))
            ->assertOk()
            ->assertSeeText('REVIEW-FIVE')
            ->assertSeeText('REVIEW-PENDING')
            ->assertSee('href="'.route('seller.penilaian.index', ['tab' => 'semua']).'"', false)
            ->assertSee('aria-current="page"', false);

        for ($i = 1; $i <= 11; $i++) {
            $this->createReview($store, 4, sprintf('REVIEW-PAGE-%02d', $i));
        }

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index', ['tab' => 'bintang_4']))
            ->assertOk()
            ->assertSee('tab=bintang_4&amp;page=2', false);
    }

    public function test_empty_store_reviews_show_empty_state_without_fake_rating(): void
    {
        [$seller] = $this->sellerWithStore();

        $this->actingAs($seller)
            ->get(route('seller.penilaian.index'))
            ->assertOk()
            ->assertSeeText('Belum ada penilaian')
            ->assertDontSeeText('Berdasarkan 0 penilaian')
            ->assertDontSeeText('5,0');
    }

    public function test_seller_can_reply_once_to_an_owned_review(): void
    {
        [$seller, $store] = $this->sellerWithStore();
        $review = $this->createReview($store, 5, 'Belum dibalas');

        $this->actingAs($seller)
            ->post(route('seller.penilaian.reply', $review), ['balasan' => 'Terima kasih atas ulasannya.'])
            ->assertRedirect(route('seller.penilaian.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'balasan' => 'Terima kasih atas ulasannya.',
        ]);
        $this->assertNotNull($review->fresh()->dibalas_at);

        $this->actingAs($seller)
            ->post(route('seller.penilaian.reply', $review), ['balasan' => 'Balasan kedua.'])
            ->assertStatus(409);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'balasan' => 'Terima kasih atas ulasannya.',
        ]);
    }

    public function test_seller_cannot_reply_to_another_store_review_or_submit_invalid_reply(): void
    {
        [$seller] = $this->sellerWithStore();
        [, $otherStore] = $this->sellerWithStore();
        $foreignReview = $this->createReview($otherStore, 4, 'Review milik toko lain');

        $this->actingAs($seller)
            ->post(route('seller.penilaian.reply', $foreignReview), ['balasan' => 'Tidak boleh'])
            ->assertNotFound();

        $ownedReview = $this->createReview(Store::query()->where('user_id', $seller->id)->firstOrFail(), 3, 'Review saya');
        $this->actingAs($seller)
            ->from(route('seller.penilaian.index'))
            ->post(route('seller.penilaian.reply', $ownedReview), ['balasan' => str_repeat('x', 1001)])
            ->assertRedirect(route('seller.penilaian.index'))
            ->assertSessionHasErrors('balasan');

        $this->assertNull($ownedReview->fresh()->balasan);
    }

    private function sellerWithStore(): array
    {
        $seller = User::factory()->create();
        $store = Store::query()->create([
            'user_id' => $seller->id,
            'name' => 'Toko '.$seller->id,
            'business_categories' => ['hasil_ternak'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Alamat toko',
            'status' => StoreStatus::Active,
        ]);

        return [$seller, $store];
    }

    private function createReview(
        Store $store,
        int $rating,
        string $comment,
        ?string $photo = null,
        ?string $reply = null
    ): Review {
        $buyer = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'ORD-'.uniqid(),
            'buyer_id' => $buyer->id,
            'store_id' => $store->id,
            'nama_item' => 'Item '.$comment,
            'tipe_item' => 'produk',
            'jumlah' => 1,
            'satuan' => 'kg',
            'harga_satuan' => 10000,
            'total_harga' => 10000,
            'metode_bayar' => 'transfer_bank',
            'status' => 'selesai',
            'nama_penerima' => $buyer->name,
            'no_hp_penerima' => '08123456789',
            'alamat_penerima' => 'Alamat',
            'kota_kabupaten_penerima' => 'Banyuwangi',
            'provinsi_penerima' => 'Jawa Timur',
        ]);

        return Review::query()->create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'buyer_id' => $buyer->id,
            'rating' => $rating,
            'komentar' => $comment,
            'foto_path' => $photo,
            'balasan' => $reply,
            'dibalas_at' => $reply !== null ? now() : null,
        ]);
    }
}
