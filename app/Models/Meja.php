<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meja extends Model
{
    protected $table = 'meja';
    // gunakan primary key default 'id' sesuai migration

    protected $fillable = ['nomor_meja', 'lantai', 'status', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_meja', 'id');
    }

    public function detailReservasi()
    {
        // detail_reservasi.id_meja -> meja.id
        return $this->hasMany(DetailReservasi::class, 'id_meja', 'id');
    }
}
