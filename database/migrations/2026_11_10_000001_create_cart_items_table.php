<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('tipe', 20);                 // batch | produk (ternak individual tidak masuk keranjang)
            $table->unsignedBigInteger('item_id');
            $table->decimal('jumlah', 12, 2)->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'tipe', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
