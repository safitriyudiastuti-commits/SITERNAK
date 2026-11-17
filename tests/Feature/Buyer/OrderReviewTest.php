<?php

namespace Tests\Feature\Buyer;

use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_review_their_completed_order_with_optional_photo(): void
    {
        Storage::fake('public');
        [$buyer, $store] = $this->buyerAndStore();
        $order = $this->createOrder($buyer, $store, 'selesai');
        $photo = UploadedFile::fake()->image('review.jpg');

        $this->actingAs($buyer)
            ->post(route('buyer.ulasan.store', $order), [
                'rating' => 5,
                'komentar' => 'Barang sesuai pesanan.',
                'foto' => $photo,
            ])
            ->assertRedirect(route('buyer.pesanan.show', $order))
            ->assertSessionHas('success');

        $review = Review::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame($store->id, $review->store_id);
        $this->assertSame($buyer->id, $review->buyer_id);
        $this->assertSame(5, $review->rating);
        $this->assertSame('Barang sesuai pesanan.', $review->komentar);
        $this->assertNotNull($review->foto_path);
        Storage::disk('public')->assertExists($review->foto_path);

        $this->actingAs($buyer)
            ->get(route('buyer.pesanan.show', $order))
            ->assertOk()
            ->assertSeeText('Ulasan Anda')
            ->assertSeeText('Barang sesuai pesanan.')
            ->assertSeeText('5/5')
            ->assertDontSeeText('Kirim Ulasan');
    }

    public function test_buyer_can_review_their_completed_order_without_photo_or_comment(): void
    {
        [$buyer, $store] = $this->buyerAndStore();
        $order = $this->createOrder($buyer, $store, 'selesai');

        $this->actingAs($buyer)
            ->post(route('buyer.ulasan.store', $order), ['rating' => 4])
            ->assertRedirect(route('buyer.pesanan.show', $order));

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 4,
            'komentar' => null,
            'foto_path' => null,
        ]);
    }

    public function test_buyer_cannot_review_another_buyers_order_or_an_incomplete_order(): void
    {
        [$buyer, $store] = $this->buyerAndStore();
        $otherBuyer = User::factory()->create();
        $foreignOrder = $this->createOrder($otherBuyer, $store, 'selesai');
        $incompleteOrder = $this->createOrder($buyer, $store, 'dikirim');

        $this->actingAs($buyer)
            ->post(route('buyer.ulasan.store', $foreignOrder), ['rating' => 5])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->post(route('buyer.ulasan.store', $incompleteOrder), ['rating' => 5])
            ->assertStatus(422);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_buyer_cannot_create_a_second_review_for_the_same_order(): void
    {
        [$buyer, $store] = $this->buyerAndStore();
        $order = $this->createOrder($buyer, $store, 'selesai');
        Review::query()->create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'buyer_id' => $buyer->id,
            'rating' => 3,
            'komentar' => 'Ulasan awal',
        ]);

        $this->actingAs($buyer)
            ->post(route('buyer.ulasan.store', $order), ['rating' => 5])
            ->assertStatus(409);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_completed_order_detail_shows_review_form_only_to_its_buyer_until_reviewed(): void
    {
        [$buyer, $store] = $this->buyerAndStore();
        $order = $this->createOrder($buyer, $store, 'selesai');

        $this->actingAs($buyer)
            ->get(route('buyer.pesanan.show', $order))
            ->assertOk()
            ->assertSee(route('buyer.ulasan.store', $order), false)
            ->assertSee('name="rating"', false)
            ->assertSee('name="komentar"', false)
            ->assertSee('name="foto"', false);

        Review::query()->create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'buyer_id' => $buyer->id,
            'rating' => 5,
            'balasan' => 'Terima kasih.',
            'dibalas_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('buyer.pesanan.show', $order))
            ->assertOk()
            ->assertSeeText('Balasan dari toko')
            ->assertSeeText('Terima kasih.')
            ->assertDontSeeText('Kirim Ulasan');

        $this->actingAs(User::factory()->create())
            ->get(route('buyer.pesanan.show', $order))
            ->assertNotFound();
    }

    public function test_review_validation_rejects_bad_rating_comment_and_photo(): void
    {
        Storage::fake('public');
        [$buyer, $store] = $this->buyerAndStore();
        $order = $this->createOrder($buyer, $store, 'selesai');

        $this->actingAs($buyer)
            ->from(route('buyer.pesanan.show', $order))
            ->post(route('buyer.ulasan.store', $order), [
                'rating' => 6,
                'komentar' => str_repeat('x', 1001),
                'foto' => UploadedFile::fake()->create('review.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('buyer.pesanan.show', $order))
            ->assertSessionHasErrors(['rating', 'komentar', 'foto']);

        $this->assertDatabaseCount('reviews', 0);
    }

    private function buyerAndStore(): array
    {
        $buyer = User::factory()->create();
        $owner = User::factory()->create();
        $store = Store::query()->create([
            'user_id' => $owner->id,
            'name' => 'Toko Ulasan',
            'business_categories' => ['hasil_ternak'],
            'city' => 'Banyuwangi',
            'province' => 'Jawa Timur',
            'address' => 'Alamat toko',
            'status' => StoreStatus::Active,
        ]);

        return [$buyer, $store];
    }

    private function createOrder(User $buyer, Store $store, string $status): Order
    {
        return Order::query()->create([
            'nomor_order' => 'ORD-REVIEW-'.uniqid(),
            'buyer_id' => $buyer->id,
            'store_id' => $store->id,
            'nama_item' => 'Produk ulasan',
            'tipe_item' => 'produk',
            'jumlah' => 2,
            'satuan' => 'kg',
            'harga_satuan' => 50000,
            'total_harga' => 100000,
            'metode_bayar' => 'transfer_bank',
            'status' => $status,
            'nama_penerima' => 'Pembeli',
            'no_hp_penerima' => '08123456789',
            'alamat_penerima' => 'Alamat penerima',
            'kota_kabupaten_penerima' => 'Banyuwangi',
            'provinsi_penerima' => 'Jawa Timur',
        ]);
    }
}
