<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedigrees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('livestock_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sire_name')->nullable();
            $table->string('sire_breed')->nullable();
            $table->string('dam_name')->nullable();
            $table->string('dam_breed')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedigrees');
    }
};
