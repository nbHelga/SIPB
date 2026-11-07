<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_reservasi', function (Blueprint $table) {
            $table->id();    
            $table->foreignId('id_meja')->nullable()->constrained('meja')->nullOnDelete();
            $table->foreignId('id_reservasi')->nullable()->constrained('reservasi')->nullOnDelete();
            $table->timestamps();
                  
            // Opsional: Mencegah meja yang sama di-assign dua kali pada SATU reservasi yang sama
            $table->unique(['id_reservasi', 'id_meja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_reservasi');
    }
};
