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
        'total_harga',
        'status',
        'completed_at'
        ];

    protected $casts = [
        'completed_at' => 'datetime',
        'total_harga' => 'decimal:2',
    ];

    public function setStatusAttribute($value): void
    {
        $normalized = ucfirst(strtolower(trim((string) $value)));
        $validStatuses = [
            'Waiting'    => 'Waiting',
            'Pending'    => 'Pending',
            'Paid'       => 'Paid',
            'Processing' => 'Processing',
            'Cancelled'  => 'Cancelled',
            'Completed'  => 'Completed',
            'Confirmed'  => 'Confirmed',
            'Reserved'   => 'Reserved',
        ];
        $this->attributes['status'] = $validStatuses[$normalized] ?? $normalized;
    }

    // Status constants
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WAITING = 'waiting';
    
    public static $statuses = [
        self::STATUS_WAITING => 'Waiting for approval',
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

    public function latestPayment()
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan', 'id')->latestOfMany('id');
    }

    public function successfulPayment()
    {
        return $this->hasOne(Pembayaran::class, 'id_pesanan', 'id')
            ->where('status', 'Success')
            ->latestOfMany('id');
    }
}
