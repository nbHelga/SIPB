<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'username' => 'admin_sipb',
                'password' => Hash::make('password123'),
                'status' => 'admin',
                'created_at' => now(),
            ],
            [
                'username' => 'kasir1',
                'password' => Hash::make('password123'),
                'status' => 'kasir',
                'created_at' => now(),
            ],
            [
                'username' => 'kasir2',
                'password' => Hash::make('password123'),
                'status' => 'kasir',
                'created_at' => now(),
            ],
        ]);
    }
}
