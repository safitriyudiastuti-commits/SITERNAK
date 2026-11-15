<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Nomor pesanan
            $table->string('nomor_order')->unique();

            // Relasi pembeli, toko, dan ternak
            $table->foreignId('buyer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->foreignId('livestock_id')
                ->nullable()
                ->constrained('livestock')
                ->nullOnDelete();

            // Snapshot informasi barang saat dipesan
            $table->string('nama_item');
            $table->string('tipe_item')->default('individual');
            $table->decimal('jumlah', 12, 2);
            $table->string('satuan')->nullable();
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('total_harga', 15, 2);

            // Pembayaran
            $table->string('metode_bayar', 30);
            $table->string('status')->default('menunggu_konfirmasi');

            // Informasi penerima
            $table->string('nama_penerima');
            $table->string('no_hp_penerima', 30);
            $table->text('alamat_penerima');
            $table->string('kota_kabupaten_penerima');
            $table->string('provinsi_penerima');

            // Informasi penyelesaian
            $table->timestamp('diterima_at')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index(['buyer_id', 'status']);
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};