<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('livestock_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_photos');
    }
};
