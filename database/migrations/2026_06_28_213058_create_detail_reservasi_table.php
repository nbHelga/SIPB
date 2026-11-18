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
            $table->foreignId('id_reservasi')->constrained('reservasi', 'id')->cascadeOnDelete();
            $table->foreignId('id_meja')->constrained('meja', 'id')->restrictOnDelete(); //mencegah delete kalau induk meja dihapus
            $table->timestamps();

            $table->unique(['id_reservasi', 'id_meja']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_reservasi');
    }
};
