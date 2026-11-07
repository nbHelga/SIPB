<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailReservasi extends Model
{
    protected $table = 'detail_reservasi';

    protected $fillable = ['id_reservasi', 'id_meja'];

    public function reservasi()
    {
        return $this->belongsTo(Reservasi::class, 'id_reservasi', 'id');
    }

    public function meja()
    {
        return $this->belongsTo(Meja::class, 'id_meja', 'id');
    }

}
