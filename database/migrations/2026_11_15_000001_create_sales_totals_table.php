<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ringkasan penjualan per toko per bulan. Admin hanya melihat total,
        // bukan rincian transaksi individual (sesuai aturan privasi di prototipe).
        Schema::create('sales_totals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('store_name');
            $table->string('owner_name');
            $table->string('city');
            $table->string('province');
            $table->string('district')->nullable();               // kecamatan
            $table->date('period');                               // tanggal 1 pada bulan yang dirangkum
            $table->unsignedBigInteger('total_sales')->default(0); // rupiah
            $table->timestamps();
            $table->unique(['store_name', 'city', 'period']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_totals');
    }
};
