<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete()
                ->index();
            $table->foreignId('buyer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('komentar')->nullable();
            $table->string('foto_path')->nullable();
            $table->text('balasan')->nullable();
            $table->timestamp('dibalas_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
