<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock_contest_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('livestock_id')->constrained('livestock')->cascadeOnDelete();
            $table->string('contest_name', 150);
            $table->string('category', 100);
            $table->string('rank', 100);
            $table->date('contest_date');
            $table->string('organizer', 150);
            $table->string('certificate_path');
            $table->string('certificate_original_name');
            $table->string('certificate_mime_type', 100);
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_contest_histories');
    }
};
