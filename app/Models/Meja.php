<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meja extends Model
{
    protected $table = 'meja';
    // gunakan primary key default 'id' sesuai migration

    protected $fillable = ['nomor_meja', 'lantai', 'status'];

    public function pesanan()
    {
        // Pesanan.nomor_meja menyimpan FK ke meja.id
        return $this->hasMany(Pesanan::class, 'nomor_meja', 'id');
    }

    public function detailReservasi()
    {
        // detail_reservasi.id_meja -> meja.id
        return $this->hasMany(DetailReservasi::class, 'id_meja', 'id');
    }
}
