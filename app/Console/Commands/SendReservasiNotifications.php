<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservasi;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendReservasiNotifications extends Command
{
    protected $signature = 'reservasi:send-notifs {--now}';
    protected $description = 'Mengirim pengingat WhatsApp H-1 untuk reservasi aktif.';

    public function handle(FonnteService $fonnte): int
    {
        $this->info('Memeriksa reservasi H-1 WIB.');

        try {
            $baseQuery = Reservasi::with([
                    'customer',
                    'pesanan:id,kode_pesanan',
                    'detailReservasi.meja',
                ])
                ->whereHas('pesanan', function ($query) {
                    $query->whereRaw("LOWER(TRIM(status)) IN ('reserved', 'confirmed')")
                        ->whereHas('pembayaran', function ($payment) {
                            $payment->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
                                ->whereRaw("LOWER(TRIM(status)) = 'success'");
                        });
                });

            if ($this->option('now')) {
                $this->warn('Mode tes: memproses satu reservasi aktif terbaru.');
                $reservasis = $baseQuery->latest('id')->take(1)->get();
            } else {
                $tomorrowWib = Carbon::now('Asia/Jakarta')->addDay()->toDateString();
                $reservasis = $baseQuery->whereDate('tanggal', $tomorrowWib)->get();
            }

            foreach ($reservasis as $r) {
                $customer = $r->customer;
                if (!$customer) {
                    continue;
                }

                $reservationDate = Carbon::parse($r->tanggal, 'Asia/Jakarta')->translatedFormat('D, d M Y');
                $detailUrl = route('customer.reservasi.detail', [
                    'kode_pesanan' => $r->pesanan->kode_pesanan,
                ]);
                $tableNumber = $r->detailReservasi->first()?->meja?->nomor_meja ?? '-';
                // $customerMessage = "PENGINGAT RESERVASI BESOK\n"
                //     . "{$reservationDate}, {$r->jam_mulai}–{$r->jam_selesai} WIB\n"
                //     . "Tamu: {$r->jml_org} orang\n"
                //     . "Lokasi: BigBang Eatery\n"
                //     . "Detail: {$detailUrl}";
                // $adminMessage = "PENGINGAT RESERVASI BESOK\n"
                //     . "{$customer->nama} ({$customer->nomor_telp})\n"
                //     . "Jadwal: {$reservationDate}, {$r->jam_mulai}–{$r->jam_selesai} WIB\n"
                //     . "Tamu: {$r->jml_org} orang | Meja: {$tableNumber}\n"
                //     . "Detail: " . route('admin.dashboard');
                $customerMessage = "⏰ *Pengingat Reservasi Besok!*\n\n"
                    . "Halo *{$customer->nama}*, kami tunggu kehadirannya besok di BigBang Eatery.\n\n"
                    . "📅 Tgl: *{$reservationDate}*\n"
                    . "⏰ Jam: *" . date('H.i', strtotime($r->jam_mulai)) . " - " . date('H.i', strtotime($r->jam_selesai)) . " WIB*\n"
                    . "👥 Jml: *{$r->jml_org} Orang*\n\n"
                    . "📍 Lokasi: BigBang Eatery\n"
                    . "📌 Detail: {$detailUrl}";

                $adminMessage = "⏰ *Reminder Reservasi Besok*\n\n"
                    . "👤 *{$customer->nama}* | ⏰ *" . date('H.i', strtotime($r->jam_mulai)) . "* | 👥 *{$r->jml_org} Pax*\n"
                    . "HP: {$customer->nomor_telp} | Meja: *{$tableNumber}*\n\n";
                    // . "Cek admin: " . route('admin.dashboard');

                $this->sendOnce($r, 'customer_reminder_notif', $customer->nomor_telp, $customerMessage, $fonnte);
                $this->sendOnce($r, 'admin_reminder_notif', env('FONNTE_PHONE'), $adminMessage, $fonnte);
            }

            $this->info('Pemeriksaan reminder selesai.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim reminder reservasi: ' . $e->getMessage());
            Log::error('[SendReservasiNotifications] Reminder gagal diproses.', [
                'message' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    private function sendOnce(Reservasi $reservasi, string $flag, ?string $phone, string $message, FonnteService $fonnte): void
    {
        $claimed = Reservasi::whereKey($reservasi->id)
            ->where(function ($query) use ($flag) {
                $query->whereNull($flag)->orWhere($flag, false);
            })
            ->update([$flag => true]);

        if (!$claimed) {
            return;
        }

        if (!$fonnte->send($phone, $message)) {
            Reservasi::whereKey($reservasi->id)->update([$flag => false]);
            Log::warning('WhatsApp reminder reservasi gagal dikirim.', [
                'reservasi_id' => $reservasi->id,
                'recipient_flag' => $flag,
            ]);
            $this->warn("Gagal mengirim {$flag} untuk reservasi #{$reservasi->id}.");
            return;
        }

        $this->line("Reminder {$flag} terkirim untuk reservasi #{$reservasi->id}.");
    }
}