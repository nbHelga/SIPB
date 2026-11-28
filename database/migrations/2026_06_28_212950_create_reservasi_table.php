<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_customer')->constrained('customer', 'id')->cascadeOnDelete();
            $table->foreignId('id_pesanan')->unique()->constrained('pesanan', 'id')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('jml_org');
            $table->text('keterangan')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('dp_forfeited')->default(false);
            $table->timestamps();

            // Penambahan Index Composite
            $table->index(
                ['created_at', 'id'],
                'reservasi_created_id_idx'
            );

            $table->index(['tanggal', 'jam_mulai', 'jam_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasi');
    }
};
