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
            // $table->decimal('dp_bayar', 12, 2)->default(0); // dipindah di tabel pembayaran yg baru
            // $table->enum('status', ['Pending', 'Confirmed', 'Completed', 'Cancelled'])->default('Pending');
            // $table->boolean('status_notif')->default(false);
            $table->boolean('customer_notif')->default(false);
            $table->boolean('admin_notif')->default(false);
            $table->timestamps();

            // Penambahan Index Composite
            $table->index(
                ['created_at', 'id'],
                'reservasi_created_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasi');
    }
};
