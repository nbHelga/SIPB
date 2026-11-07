<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customer';

    protected $fillable = ['nama', 'nomor_telp'];

    public function reservasi()
    {
        return $this->hasMany(Reservasi::class, 'id_customer', 'id');
    }
}
