<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fitur Profil toko & Mode libur. Aman dijalankan walau sebagian kolom sudah ada. */
return new class extends Migration
{
    private array $columns = [
        'description', 'contact_phone', 'logo_path', 'business_hours',
        'vacation_mode', 'vacation_start', 'vacation_end', 'vacation_message', 'vacation_hide_listings',
    ];

    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('stores', 'contact_phone')) {
                $table->string('contact_phone', 20)->nullable();
            }
            if (! Schema::hasColumn('stores', 'logo_path')) {
                $table->string('logo_path')->nullable();
            }
            if (! Schema::hasColumn('stores', 'business_hours')) {
                $table->json('business_hours')->nullable();
            }
            if (! Schema::hasColumn('stores', 'vacation_mode')) {
                $table->boolean('vacation_mode')->default(false);
            }
            if (! Schema::hasColumn('stores', 'vacation_start')) {
                $table->date('vacation_start')->nullable();
            }
            if (! Schema::hasColumn('stores', 'vacation_end')) {
                $table->date('vacation_end')->nullable();
            }
            if (! Schema::hasColumn('stores', 'vacation_message')) {
                $table->string('vacation_message')->nullable();
            }
            if (! Schema::hasColumn('stores', 'vacation_hide_listings')) {
                $table->boolean('vacation_hide_listings')->default(false);
            }
        });
    }

    public function down(): void
    {
        $ada = array_values(array_filter($this->columns, fn ($c) => Schema::hasColumn('stores', $c)));

        if ($ada) {
            Schema::table('stores', fn (Blueprint $table) => $table->dropColumn($ada));
        }
    }
};