<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'id' => (string) Str::uuid(),
            'nama' => 'Admin Utama',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'alamat' => 'Jl. Raya No. 1, Jakarta',
            'no_kendaraan' => 'B1234XYZ',
            'nama_kendaraan' => 'Avanza 2022',
            'profile' => '',
        ]);

        User::create([
            'id' => (string) Str::uuid(),
            'nama' => 'User Biasa',
            'email' => 'user@example.com',
            'password' => 'password',
            'role' => 'customer',
            'alamat' => 'Jl. Kedua No. 2, Bandung',
            'no_kendaraan' => 'D5678ABC',
            'nama_kendaraan' => 'Honda Brio',
            'profile' => '',
        ]);
    }
}
