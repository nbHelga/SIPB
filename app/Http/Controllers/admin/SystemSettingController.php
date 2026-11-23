<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function edit(SystemSettings $settings)
    {
        return view('admin.settings', ['settings' => $settings->all()]);
    }

    public function update(Request $request, SystemSettings $settings)
    {
        $validated = $request->validate([
            'selforder_cash_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'selforder_qris_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'reservation_dp_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'reservation_minimum_dp' => ['required', 'integer', 'min:1'],
            'reservation_regular_max_guests' => ['required', 'integer', 'min:1', 'max:100'],
            'reservation_max_guests' => ['required', 'integer', 'min:1', 'max:500'],
            'table_default_capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'payment_status_poll_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'admin_notification_poll_seconds' => ['required', 'integer', 'min:1', 'max:300'],
        ]);
        $settings->update($validated);
        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
