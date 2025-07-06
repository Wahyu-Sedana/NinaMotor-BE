<?php

namespace Database\Seeders;

use App\Models\KategoriSparepart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = [
            ['nama' => 'Mesin', 'deskripsi' => 'Komponen utama mesin kendaraan'],
            ['nama' => 'Elektrikal', 'deskripsi' => 'Komponen kelistrikan kendaraan'],
            ['nama' => 'Bodi', 'deskripsi' => 'Komponen bodi dan aksesoris'],
        ];

        foreach ($kategori as $item) {
            KategoriSparepart::create($item);
        }
    }
}
