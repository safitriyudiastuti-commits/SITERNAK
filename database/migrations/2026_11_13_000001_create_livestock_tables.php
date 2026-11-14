<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_type_id')->constrained('commodity_types')->restrictOnDelete();
            $table->string('mode', 20);
            $table->string('purpose', 40)->nullable();
            $table->string('nama_kode', 100)->nullable();
            $table->string('ras', 100)->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->unsignedSmallInteger('umur')->nullable();
            $table->decimal('berat', 8, 2)->nullable();
            $table->unsignedInteger('jumlah')->nullable();
            $table->decimal('harga', 12, 2);
            $table->string('satuan_jual', 50);
            $table->unsignedInteger('stok')->default(1);
            $table->string('status', 20)->default('aktif')->index();
            $table->timestamps();

            $table->index(['store_id', 'commodity_type_id']);
        });

        Schema::create('livestock_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_id')->constrained('livestock')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->string('type', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_media');
        Schema::dropIfExists('livestock');
    }
};
