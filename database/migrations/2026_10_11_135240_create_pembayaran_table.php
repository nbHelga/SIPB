<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->constrained('pesanan')->cascadeOnDelete();
            
            // Apakah ini DP, Pelunasan, atau Pembayaran Penuh (Self Order)
            $table->enum('jenis', ['DP', 'Pelunasan', 'Full']); 
            
            // Tunai, Midtrans, QRIS Kasir, dll
            $table->string('metode'); 
            
            // Berapa nominal yang dibayarkan untuk tahap ini
            $table->decimal('nominal', 12, 2); 
            
            // Hanya diisi jika metode_pembayaran = tunai
            $table->decimal('uang_diterima', 12, 2)->nullable();
            $table->decimal('uang_kembali', 12, 2)->nullable();
            
            // Hanya diisi jika via Midtrans
            $table->string('snap_token')->nullable(); 
            $table->string('midtrans_order_id')->nullable()->unique();
            
            // Pending (Midtrans blm dibayar), Success, Failed
            $table->enum('status', ['Pending', 'Success', 'Failed'])->default('Pending'); 
            
            $table->timestamps();

            $table->index(
                ['id_pesanan', 'jenis', 'status', 'id'],
                'pembayaran_order_jenis_status_id_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
