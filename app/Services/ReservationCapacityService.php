<?php

namespace App\Services;

use App\Models\Meja;
use App\Models\Reservasi;
use Illuminate\Database\Eloquent\Builder;

class ReservationCapacityService
{
    public const GUESTS_PER_TABLE = 4;

    /**
     * Menghitung batas maksimum jumlah orang yang dapat dimasukkan customer.
     * Standar minimal adalah 100 orang (karena rombongan besar >10 pax akan diatur manual oleh admin).
     * Namun jika (jumlah meja aktif x 4) melebihi 100 (misal 30 meja x 4 = 120),
     * maka batas maksimum otomatis mengikuti kapasitas meja (120).
     */
    public static function maxGuestsLimit(): int
    {
        $activeTableCount = Meja::query()
            ->where('is_active', true)
            ->count();

        return max(100, $activeTableCount * self::GUESTS_PER_TABLE);
    }

    public function check(
        string $date,
        string $start,
        string $end,
        int $guestCount,
        ?int $excludeReservationId = null
    ): array {
        $activeTableCount = Meja::query()
            ->where('is_active', true)
            ->count();
        $totalGuestCapacity = $activeTableCount * self::GUESTS_PER_TABLE;
        $pendingCutoff = now('UTC')->subMinutes(
            max(1, (int) config('services.reservation.payment_window_minutes', 60))
        );

        $overlappingReservations = Reservasi::query()
            ->whereDate('tanggal', $date)
            ->where('jam_mulai', '<', $end)
            ->where('jam_selesai', '>', $start)
            ->when($excludeReservationId, fn (Builder $query) =>
                $query->whereKeyNot($excludeReservationId)
            )
            ->whereHas('pesanan', function (Builder $orders) use ($pendingCutoff) {
                $orders->where(function (Builder $active) use ($pendingCutoff) {
                    $active->whereIn('status', ['Reserved', 'Confirmed', 'Processing', 'Waiting'])
                        ->orWhere(function (Builder $pending) use ($pendingCutoff) {
                            $pending->where('status', 'Pending')
                                    ->where('created_at', '>=', $pendingCutoff)
                                    ->whereHas('pembayaran', function (Builder $payments) {
                                        $payments->where('jenis', 'DP')
                                            ->where('status', 'Pending');
                                    });
                        });
                });
            })
            ->withCount([
                'detailReservasi as active_tables_count' => fn (Builder $details) =>
                    $details->whereHas('meja', fn (Builder $tables) =>
                        $tables->where('is_active', true)
                    ),
            ])
            ->get(['id', 'jml_org']);

        $usedTables = $overlappingReservations->sum(function (Reservasi $reservasi): int {
            $allocatedCount = (int) $reservasi->active_tables_count;

            return $allocatedCount > 0
                ? $allocatedCount
                : (int) ceil((int) $reservasi->jml_org / self::GUESTS_PER_TABLE);
        });

        $availableTables = max(0, $activeTableCount - $usedTables);
        $estimatedTablesNeeded = (int) ceil(max(0, $guestCount) / self::GUESTS_PER_TABLE);

        $available = $activeTableCount > 0 && $estimatedTablesNeeded <= $availableTables;
        $reason = $activeTableCount < 1
            ? 'Belum ada meja aktif untuk reservasi.'
            : (!$available
                ? "Kapasitas meja tidak mencukupi untuk waktu tersebut. Silakan pilih waktu lain atau hubungi admin."
                : null);

        return [
            'available' => $available,
            'message' => $reason,
            'active_tables' => $activeTableCount,
            'total_guest_capacity' => $totalGuestCapacity,
            'used_tables' => (int) $usedTables,
            'used_guests' => (int) $overlappingReservations->sum('jml_org'),
            'available_tables' => $availableTables,
            'available_guests' => $availableTables * self::GUESTS_PER_TABLE,
            'estimated_tables_needed' => $estimatedTablesNeeded,
            'remaining_tables_after_booking' => max(0, $availableTables - $estimatedTablesNeeded),
            'remaining_guests_after_booking' => max(0, ($availableTables - $estimatedTablesNeeded) * self::GUESTS_PER_TABLE),
        ];
    }
}
