<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MejaSeeder extends Seeder
{
    public function run(): void
    {
        $meja = [];
        for ($i = 1; $i <= 22; $i++) {
            $meja[] = [
                'nomor_meja' => 'M' . $i,
                'lantai' => $i <= 16 ? '1' : '2',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('meja')->insert($meja);
    }
}
