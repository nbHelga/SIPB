<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_wa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_reservasi')->constrained('reservasi', 'id')->cascadeOnDelete();
            $table->enum('jenis_notifikasi', [
                'dp_customer',
                'dp_admin',
                'reminder_customer',
                'reminder_admin',
                'approval_pending_customer',
                'approval_pending_admin',
                'approval_decision_customer',
            ]);
            $table->enum('status_kirim', ['pending', 'sent', 'failed'])->default('sent');
            $table->timestamp('waktu_kirim')->nullable();
            $table->timestamps();

            $table->unique(['id_reservasi', 'jenis_notifikasi'], 'notifikasi_wa_res_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_wa');
    }
};
