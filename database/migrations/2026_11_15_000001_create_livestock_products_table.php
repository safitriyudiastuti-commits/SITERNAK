<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('livestock_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_type_id')->constrained('commodity_types')->restrictOnDelete();
            $table->decimal('stok', 12, 3)->default(0);
            $table->string('satuan', 50);
            $table->decimal('harga', 12, 2);
            $table->date('tanggal_produksi');
            $table->date('tanggal_kedaluwarsa')->index();
            $table->string('grade', 50)->nullable();
            $table->boolean('penyimpanan_dingin')->default(false);
            $table->string('asal_ternak', 150)->nullable();
            $table->timestamps();

            $table->index(['store_id', 'commodity_type_id']);
        });

        Schema::create('livestock_product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_product_id')
                ->constrained('livestock_products')
                ->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livestock_product_media');
        Schema::dropIfExists('livestock_products');
    }
};
