<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('nama_toko', 120);
            $t->string('nama_pemilik', 120);
            $t->json('kategori_usaha');
            $t->string('kecamatan', 100)->nullable();
            $t->string('kota_kabupaten', 100);
            $t->string('provinsi', 100);
            $t->text('alamat');
            $t->string('foto_ktp_path');
            $t->string('foto_lokasi_path');
            $t->string('buku_rekening_path');
            $t->string('bank_nama', 60);
            $t->text('bank_no_rekening');      // terenkripsi (cast 'encrypted')
            $t->string('bank_atas_nama', 120);
            $t->enum('status', ['pending_verification', 'active', 'rejected'])->default('pending_verification');
            $t->text('catatan_admin')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_applications');
    }
};
