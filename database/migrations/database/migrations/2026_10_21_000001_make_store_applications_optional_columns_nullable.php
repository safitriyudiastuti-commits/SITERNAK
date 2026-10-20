<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_applications', function (Blueprint $t) {
            $t->string('nama_pemilik', 120)->nullable()->change();
            $t->string('bank_nama', 60)->nullable()->change();
            $t->text('bank_no_rekening')->nullable()->change();
            $t->string('bank_atas_nama', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('store_applications', function (Blueprint $t) {
            $t->string('nama_pemilik', 120)->nullable(false)->change();
            $t->string('bank_nama', 60)->nullable(false)->change();
            $t->text('bank_no_rekening')->nullable(false)->change();
            $t->string('bank_atas_nama', 120)->nullable(false)->change();
        });
    }
};