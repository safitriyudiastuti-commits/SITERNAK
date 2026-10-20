<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyiapkan tabel untuk fitur "Verifikasi pengajuan toko peternak" (UC-18).
 *
 * Migration ini AMAN dijalankan walau Teman 1 / Teman 2 sudah membuat tabel yang sama:
 * tiap tabel/kolom dicek dulu, hanya dibuat kalau belum ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. users: kolom role (admin / pembeli). Teman 1 mungkin sudah membuatnya.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('pembeli')->index();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable();
            }
        });

        // 2. stores: data toko yang diajukan peternak
        if (! Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->json('business_categories')->nullable(); // unggas, ternak_kecil, ternak_besar, hasil_ternak
                $table->string('city');
                $table->string('province');
                $table->text('address');
                $table->string('status', 30)->default('pending_verification')->index();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('terms_accepted_at')->nullable(); // pernyataan kebenaran data
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        } else {
            Schema::table('stores', function (Blueprint $table) {
                if (! Schema::hasColumn('stores', 'business_categories')) {
                    $table->json('business_categories')->nullable();
                }
                if (! Schema::hasColumn('stores', 'status')) {
                    $table->string('status', 30)->default('pending_verification')->index();
                }
                if (! Schema::hasColumn('stores', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
                if (! Schema::hasColumn('stores', 'terms_accepted_at')) {
                    $table->timestamp('terms_accepted_at')->nullable();
                }
                if (! Schema::hasColumn('stores', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable();
                }
                if (! Schema::hasColumn('stores', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable();
                }
                if (! Schema::hasColumn('stores', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        // 3. store_documents: foto KTP, foto lokasi usaha, buku rekening
        if (! Schema::hasTable('store_documents')) {
            Schema::create('store_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('type', 20); // ktp | location | bankbook
                $table->string('path');     // lokasi file di disk "local" (privat)
                $table->string('original_name')->nullable();
                $table->unsignedInteger('size')->default(0); // dalam byte
                $table->timestamps();

                $table->index(['store_id', 'type']);
            });
        }

        // 4. audit_logs: catatan permanen (append-only) keputusan admin
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 60); // store.approved, store.rejected, store.document_viewed
                $table->nullableMorphs('subject');
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak menyentuh users/stores karena dipakai fitur teman.
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('store_documents');
    }
};
