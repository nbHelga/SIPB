<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Customer; 

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Budi Santoso', 'Siti Rahma', 'Aditya Pratama', 'Dewi Lestari', 'Rizky Febrian',
            'Nadia Utami', 'Fajar Ramadhan', 'Maya Putri', 'Eko Prasetyo', 'Rina Wijaya',
            'Hendra Setiawan', 'Anisa Fitriani', 'Dedi Kurniawan', 'Lestari Anggraini', 'Bagus Nugroho',
            'Dian Sastro', 'Rudi Hartono', 'Tari Wulandari', 'Gita Gutawa', 'Aris Munandar',
            'Yuni Shara', 'Agus Harimurti', 'Chandra Wijaya', 'Fitri Carlina', 'Gilang Dirga',
            'Hesty Purwadinata', 'Irwan Mussry', 'Joko Widodo', 'Kartika Putri', 'Lukman Sardi',
            'Melly Goeslaw', 'Niko Al Hakim', 'Oky Setiana', 'Puji Astuti', 'Qory Sandioriva'
        ];

        foreach ($names as $index => $name) {
            Customer::create([
                'nama' => $name,
                'nomor_telp' => '0812' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
