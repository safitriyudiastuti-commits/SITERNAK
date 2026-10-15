<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_documents');
    }
};
