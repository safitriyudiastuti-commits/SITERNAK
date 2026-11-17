<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('livestock_product_id')
                ->nullable()
                ->after('livestock_id')
                ->constrained('livestock_products')
                ->nullOnDelete();

            $table->string('status_bayar', 30)
                ->default('belum')
                ->after('metode_bayar')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['livestock_product_id']);
            $table->dropColumn('livestock_product_id');
            $table->dropIndex(['status_bayar']);
            $table->dropColumn('status_bayar');
        });
    }
};
