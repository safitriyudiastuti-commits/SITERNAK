<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sale_mode', 20)->default('individual')->index();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->decimal('price', 14, 2);
            $table->unsignedInteger('stock')->default(1);
            $table->string('unit', 30)->default('ekor');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestocks');
    }
};
