<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livestock', function (Blueprint $table): void {
            $table->string('nama_induk_betina', 100)->nullable();
            $table->string('ras_induk_betina', 100)->nullable();
            $table->string('nama_pejantan', 100)->nullable();
            $table->string('ras_pejantan', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('livestock', function (Blueprint $table): void {
            $table->dropColumn([
                'nama_induk_betina',
                'ras_induk_betina',
                'nama_pejantan',
                'ras_pejantan',
            ]);
        });
    }
};
