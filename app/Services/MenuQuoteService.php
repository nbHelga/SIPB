<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Collection;

class MenuQuoteService
{
    public function capture(Collection $menus): array
    {
        return $menus->mapWithKeys(fn (Menu $menu) => [
            (string) $menu->id => [
                'nama' => $menu->nama,
                'harga' => (int) round((float) $menu->harga),
            ],
        ])->all();
    }

    /**
     * $lines: [['id' => 1, 'qty' => 2, 'notes' => null, 'snapshot' => [...]]]
     * reservation=false: menu harus berstatus open.
     * reservation=true: menu harus is_reservable.
     */
    public function review(array $lines, bool $reservation): array
    {
        $ids = collect($lines)->pluck('id')->map(fn ($id) => (int) $id)->unique();
        $menus = Menu::whereIn('id', $ids)->get()->keyBy('id');

        $items = [];
        $messages = [];
        $total = 0;

        foreach ($lines as $line) {
            $id = (int) ($line['id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);
            $snapshot = $line['snapshot'] ?? null;

            if ($qty < 1) {
                continue;
            }

            $menu = $menus->get($id);

            if (!$menu) {
                $messages[] = (($snapshot['nama'] ?? null) ?: 'Menu')
                    . ' sudah tidak tersedia.';
                continue;
            }

            $available = $reservation
                ? (bool) $menu->is_reservable
                : strtolower(trim((string) $menu->status)) === 'open';

            if (!$available) {
                $messages[] = "{$menu->nama} sudah tidak tersedia.";
            }

            $currentPrice = (int) round((float) $menu->harga);

            if (!is_array($snapshot)) {
                $messages[] = "Harga {$menu->nama} belum dapat diverifikasi.";
            } elseif ($currentPrice !== (int) ($snapshot['harga'] ?? -1)) {
                $messages[] = "Harga {$menu->nama} berubah dari Rp "
                    . number_format((int) $snapshot['harga'], 0, ',', '.')
                    . ' menjadi Rp '
                    . number_format($currentPrice, 0, ',', '.') . '.';
            }

            $subtotal = $currentPrice * $qty;
            $total += $subtotal;

            // View reservasi_payment menggunakan $item->menu, $item->jumlah, dst.
            $items[] = (object) [
                'menu' => $menu,
                'jumlah' => $qty,
                'subtotal' => $subtotal,
                'catatan' => $line['notes'] ?? null,
                'available' => $available,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'messages' => array_values(array_unique($messages)),
            'changed' => count($messages) > 0,
        ];
    }
}