<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = ['gambar', 'nama', 'harga', 'kategori', 'deskripsi', 'status', 'is_bestseller', 'is_reservable'];

    protected $casts = [
        'harga' => 'decimal:2',
        // 'stok' => 'integer',
        'is_bestseller' => 'boolean',
        'is_reservable' => 'boolean',
    ];

    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'id_menu', 'id');
    }
}
