<?php

namespace App\Services;

use App\Models\SystemSetting;

class SystemSettings
{
    private const DEFAULTS = [
        'selforder_cash_minutes' => 120,
        'selforder_qris_minutes' => 15,
        'reservation_dp_minutes' => 60,
        'reservation_minimum_dp' => 50000,
        'reservation_regular_max_guests' => 10,
        'reservation_max_guests' => 100,
        'table_default_capacity' => 4,
        'payment_status_poll_seconds' => 20,
        'admin_notification_poll_seconds' => 10,
    ];

    public function get(string $key): int
    {
        return max(1, (int) SystemSetting::getValue($key, self::DEFAULTS[$key] ?? 1));
    }

    public function all(): array
    {
        return collect(self::DEFAULTS)->map(fn ($default, $key) => $this->get($key))->all();
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                SystemSetting::putValue($key, $value);
            }
        }
    }
}
