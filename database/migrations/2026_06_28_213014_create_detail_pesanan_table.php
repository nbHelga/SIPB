<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pesanan')->constrained('pesanan', 'id')->cascadeOnDelete();
            $table->foreignId('id_menu')->constrained('menu', 'id')->cascadeOnDelete();
            $table->integer('jumlah');
            $table->decimal('subtotal', 12, 2);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan');
    }
};
