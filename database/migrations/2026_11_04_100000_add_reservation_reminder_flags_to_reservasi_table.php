<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservasi', function (Blueprint $table) {
            $table->boolean('customer_reminder_notif')->default(false);
            $table->boolean('admin_reminder_notif')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('reservasi', function (Blueprint $table) {
            $table->dropColumn(['customer_reminder_notif', 'admin_reminder_notif']);
        });
    }
};
