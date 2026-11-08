<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // pembeli pemilik tagihan
            $table->unsignedBigInteger('order_id')->nullable(); // disambungkan ke tabel pesanan nanti
            $table->string('order_number')->unique();
            $table->string('buyer_name');
            $table->string('store_name');
            $table->string('item_summary');
            $table->unsignedBigInteger('amount');        // nominal transfer (rupiah)
            $table->unsignedBigInteger('amount_billed'); // nominal tagihan pesanan
            $table->string('bank_name');
            $table->string('destination_account');
            $table->string('destination_name');
            $table->string('sender_name')->nullable();
            $table->timestamp('transferred_at')->nullable();   // diisi saat pembeli mengunggah bukti
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            // belum_dibayar -> menunggu -> disetujui | ditolak (ditolak -> unggah ulang -> menunggu)
            $table->string('status', 20)->default('belum_dibayar');
            $table->text('decision_note')->nullable();
            $table->timestamp('due_at')->nullable();           // batas verifikasi 1x24 jam, diisi saat bukti diunggah
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
