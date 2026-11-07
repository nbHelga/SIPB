<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('gambar')->nullable();
            $table->string('nama', 100);
            $table->decimal('harga', 12, 2);
            // $table->integer('stok')->default(0);
            $table->string('kategori', 50);
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['open', 'close'])->default('close');
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_reservable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
