<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kategori: Unggas, Ternak kecil, Ternak besar (kelompok Ternak hidup) dan Hasil ternak
        Schema::create('commodity_categories', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20);            // ternak_hidup | hasil_ternak
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Jenis: Sapi Limousin, Ayam Broiler, Daging, Telur, dst.
        // Jenis tidak pernah dihapus, hanya dinonaktifkan (is_active = false).
        Schema::create('commodity_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('commodity_categories');
            $table->string('name');
            $table->json('selling_modes');               // ["individual","batch","produk"]
            $table->json('purposes')->nullable();        // ["bibit","pembesaran","siap_potong","indukan_kontes"]
            $table->json('form_fields')->nullable();     // {"jenis_kelamin": true, "ras": false, ...} true = wajib diisi
            $table->json('contest_categories')->nullable(); // ["Postur","Bobot badan","Tanduk"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_types');
        Schema::dropIfExists('commodity_categories');
    }
};
