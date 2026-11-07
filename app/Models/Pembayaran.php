<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_pesanan',
        'jenis',
        'metode', 
        'nominal', 
        'uang_diterima',
        'uang_kembali',
        'snap_token',
        'midtrans_order_id',
        'status'];

    protected $casts = [
        'nominal' => 'decimal:2',
        'uang_diterima' => 'decimal:2',
        'uang_kembali' => 'decimal:2',
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id');
    }

}
