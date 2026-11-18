<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservasi;
use App\Models\NotifikasiWa;
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
                    $query->whereIn('status', ['Reserved', 'Confirmed'])
                        ->whereHas('pembayaran', function ($payment) {
                            $payment->where('jenis', 'DP')
                                ->where('status', 'Success');
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

                $this->sendOnce($r, 'reminder_customer', $customer->nomor_telp, $customerMessage, $fonnte);
                $this->sendOnce($r, 'reminder_admin', config('services.fonnte.phone'), $adminMessage, $fonnte); // FIX SECURITY-04
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

    private function sendOnce(Reservasi $reservasi, string $jenisNotifikasi, ?string $phone, string $message, FonnteService $fonnte): void
    {
        if (NotifikasiWa::sudahTerkirim($reservasi->id, $jenisNotifikasi)) {
            return;
        }

        if (empty($phone)) {
            return;
        }

        if (!$fonnte->send($phone, $message)) {
            NotifikasiWa::catatGagal($reservasi->id, $jenisNotifikasi);
            Log::warning('WhatsApp reminder reservasi gagal dikirim.', [
                'reservasi_id' => $reservasi->id,
                'jenis_notifikasi' => $jenisNotifikasi,
            ]);
            $this->warn("Gagal mengirim {$jenisNotifikasi} untuk reservasi #{$reservasi->id}.");
            return;
        }

        NotifikasiWa::catatTerkirim($reservasi->id, $jenisNotifikasi);
        $this->line("Reminder {$jenisNotifikasi} terkirim untuk reservasi #{$reservasi->id}.");
    }
}