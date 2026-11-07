<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $table = 'reservasi';

    protected $fillable = [
        'id_customer', 
        'id_pesanan',
        'tanggal', 
        'jam_mulai', 
        'jam_selesai', 
        'jml_org', 
        'keterangan', 
        // 'dp_bayar', 
        // 'status_notif'
        'customer_notif',
        'admin_notif'
        ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
        'dp_bayar' => 'decimal:2',
        'jml_org' => 'integer',
        // 'status_notif' => 'boolean',
        'customer_notif' => 'boolean',
        'admin_notif' => 'boolean',
    ];

    // Status constants
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RESERVED = 'reserved';
    const STATUS_CONFIRMED = 'confirmed';
    
    public static $statuses = [
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_RESERVED => 'Reserved',
        self::STATUS_CONFIRMED => 'Confrimed'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id');
    }

    public function detailReservasi()
    {
        return $this->hasMany(DetailReservasi::class, 'id_reservasi', 'id');
    }

    public function meja()
    {
        // Many-to-many via detail_reservasi (id_meja -> meja.id)
        return $this->belongsToMany(Meja::class, 'detail_reservasi', 'id_reservasi', 'id_meja');
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id');
    }

    public function getStatusPesananAttribute()
    {
        return $this->pesanan ? $this->pesanan->status : null;
    }

    public function getDpBayarAttribute(): float
    {
        return (float) ($this->pesanan?->pembayaran()
            ->where('jenis', 'DP')
            ->where('status', 'Success')
            ->sum('nominal') ?? 0);
    }
}
