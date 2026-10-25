<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tiap kolom dicek dulu: hanya dibuat kalau belum ada.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'alamat')) {
                $table->string('alamat')->nullable();
            }
            if (! Schema::hasColumn('users', 'kota_kabupaten')) {
                $table->string('kota_kabupaten', 100)->nullable();
            }
            if (! Schema::hasColumn('users', 'provinsi')) {
                $table->string('provinsi', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        $kolom = array_values(array_filter(
            ['no_hp', 'alamat', 'kota_kabupaten', 'provinsi'],
            fn (string $nama) => Schema::hasColumn('users', $nama)
        ));

        if ($kolom !== []) {
            Schema::table('users', function (Blueprint $table) use ($kolom) {
                $table->dropColumn($kolom);
            });
        }
    }
};