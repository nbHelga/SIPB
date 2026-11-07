<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';
    protected $primaryKey = 'id';

    protected $fillable = [
        'kode_pesanan',
        'id_user',
        'id_meja', 
        // 'id_reservasi', 
        'total_harga',
        // 'pb', 
        // 'total_bayar',
        'status',
        'completed_at'
        ];
        // 'metode_pembayaran',
        // 'metode_pelunasan',
        // 'snap_token'];

    protected $casts = [
        'tanggal_pesanan' => 'datetime',
        'total_harga' => 'decimal:2',
        // 'pb' => 'decimal:2',
        // 'total_bayar' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    public static $statuses = [
        self::STATUS_PAID => 'Paid',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function meja()
    {
        // Pesanan.nomor_meja menyimpan meja.id (integer)
        return $this->belongsTo(Meja::class, 'id_meja', 'id');
    }

    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'id_pesanan', 'id');
    }

    public function reservasi()
    {
        return $this->hasOne(Reservasi::class, 'id_pesanan', 'id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'id_pesanan', 'id');
    }

    // public function getDaftarMejaAttribute()
    // {
    //     // Jika pesanan reservasi, ambil seluruh meja dari relasi detail_reservasi
    //     if ($this->id_reservasi && $this->reservasi) {
    //         return $this->reservasi->detailReservasi->pluck('meja.nomor_meja')->implode(', ');
    //     }

    //     // Jika self order, ambil langsung dari relasi id_meja
    //     return $this->meja ? $this->id_meja : '-';
    // }
}
