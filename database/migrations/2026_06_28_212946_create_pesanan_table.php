<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan', 30)->unique(); // Kode unik publik (ORD-YYYYMMDD-XXXX)
            $table->foreignId('id_user')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('id_meja')->nullable()->constrained('meja', 'id')->nullOnDelete();
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->enum('status', ['Waiting', 'Pending', 'Paid', 'Processing', 'Cancelled', 'Completed', 'Confirmed', 'Reserved'])->default('Pending');
            $table->timestamps();
            $table->timestamp('completed_at')->nullable()->index();

            // Penambahan Index Composite
            $table->index(
                ['created_at', 'status', 'id'],
                'pesanan_created_status_id_idx'
            );

            $table->index(
                ['status', 'created_at', 'id'],
                'pesanan_status_created_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
