<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPesanan extends Model
{
    protected $table = 'detail_pesanan';
    protected $primaryKey = 'id';

    protected $fillable = ['id_pesanan', 'id_menu', 'jumlah', 'subtotal', 'catatan'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'jumlah' => 'integer',
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu', 'id');
    }

    // Accessor untuk menghitung subtotal otomatis
    public function getSubtotalAttribute($value)
    {
        if (!$value && $this->menu) {
            return $this->jumlah * $this->menu->harga;
        }
        return $value;
    }
    
    // Boot method untuk auto-calculate subtotal sebelum save
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            if ($model->menu) {
                $model->subtotal = $model->jumlah * $model->menu->harga;
            }
        });
    }
}
