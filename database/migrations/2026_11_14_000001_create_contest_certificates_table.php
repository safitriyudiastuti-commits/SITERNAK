<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contest_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete(); // toko pemilik ternak
            $table->unsignedBigInteger('livestock_id')->nullable(); // disambungkan ke tabel ternak milik Teman Penjual nanti
            $table->foreignId('commodity_type_id')->nullable()->constrained('commodity_types')->nullOnDelete();
            $table->string('type_name');            // jenis, contoh: Sapi Limousin (salinan agar tetap tampil)
            $table->string('animal_name');          // nama ternak, contoh: Bima
            $table->string('store_name');           // salinan nama toko
            $table->string('city');                 // salinan kota toko
            $table->string('contest_name');
            $table->string('category');             // kategori kontes: Postur, Bobot badan, Tanduk, Suara, ...
            $table->unsignedTinyInteger('rank');    // 1, 2, 3 (Juara 1, 2, 3)
            $table->date('contest_date');
            $table->string('organizer');
            $table->string('certificate_path')->nullable();
            $table->string('certificate_original_name')->nullable();
            // menunggu -> terverifikasi | ditolak (ditolak -> peternak mengajukan ulang -> menunggu)
            $table->string('status', 20)->default('menunggu');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contest_certificates');
    }
};
