<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiWa extends Model
{
    protected $table = 'notifikasi_wa';

    protected $fillable = [
        'id_reservasi',
        'jenis_notifikasi',
        'status_kirim',
        'waktu_kirim',
    ];

    protected $casts = [
        'waktu_kirim' => 'datetime',
    ];

    public function reservasi()
    {
        return $this->belongsTo(Reservasi::class, 'id_reservasi', 'id');
    }

    /**
     * Cek apakah notifikasi untuk jenis tertentu pada reservasi ini sudah pernah terkirim sukses.
     */
    public static function sudahTerkirim(int $reservasiId, string $jenis): bool
    {
        return self::where('id_reservasi', $reservasiId)
            ->where('jenis_notifikasi', $jenis)
            ->where('status_kirim', 'sent')
            ->exists();
    }

    /**
     * Catat bahwa notifikasi WA sukses terkirim.
     */
    public static function catatTerkirim(int $reservasiId, string $jenis): self
    {
        return self::updateOrCreate(
            ['id_reservasi' => $reservasiId, 'jenis_notifikasi' => $jenis],
            ['status_kirim' => 'sent', 'waktu_kirim' => now()]
        );
    }

    /**
     * Catat bahwa notifikasi WA gagal terkirim (agar bisa dicoba ulang).
     */
    public static function catatGagal(int $reservasiId, string $jenis): self
    {
        return self::updateOrCreate(
            ['id_reservasi' => $reservasiId, 'jenis_notifikasi' => $jenis],
            ['status_kirim' => 'failed', 'waktu_kirim' => null]
        );
    }
}
