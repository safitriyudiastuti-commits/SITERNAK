<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peternak', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('nama_usaha', 120);
            $t->text('deskripsi')->nullable();
            $t->json('kategori_usaha');
            $t->string('kecamatan', 100)->nullable();
            $t->string('kota_kabupaten', 100);
            $t->string('provinsi', 100);
            $t->text('alamat');
            $t->string('kontak', 30)->nullable();
            $t->string('logo_path')->nullable();
            $t->json('jam_operasional')->nullable();

            // Mode libur (FR-PET-08)
            $t->boolean('mode_libur')->default(false);
            $t->date('libur_mulai')->nullable();
            $t->date('libur_selesai')->nullable();
            $t->string('libur_pesan')->nullable();
            $t->string('libur_aksi', 30)->nullable();

            // Rekening pencairan (terverifikasi)
            $t->string('bank_nama', 60)->nullable();
            $t->text('bank_no_rekening')->nullable(); // terenkripsi
            $t->string('bank_atas_nama', 120)->nullable();

            $t->enum('status_verifikasi', ['pending_verification', 'active', 'rejected'])->default('active');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peternak');
    }
};
