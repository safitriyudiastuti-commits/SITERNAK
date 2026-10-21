<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tiap kolom dicek dulu, supaya aman kalau Safitri sudah menambahkannya.
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('pembeli')->index();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (! Schema::hasColumn('users', 'province')) {
                $table->string('province', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Dikosongkan: kolom ini dipakai fitur lain.
    }
};
