<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contest_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('livestock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contest_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contest_name');
            $table->date('event_date')->nullable();
            $table->string('certificate_path');
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contest_records');
    }
};
