<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pembeli (default) | peternak (toko disetujui admin) | admin
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('pembeli')->after('password')->index();
            });

            return;
        }

        // Kolom role sudah ada (dibuat versi lama dengan default 'member').
        // Samakan default-nya dengan versi sekarang dan rapikan data lama.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('pembeli')->change();
        });

        DB::table('users')->where('role', 'member')->update(['role' => 'pembeli']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};