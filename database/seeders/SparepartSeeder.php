<?php

namespace Database\Seeders;

use App\Models\KategoriSparepart;
use App\Models\Sparepart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SparepartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil kategori secara acak
        $kategoriIds = KategoriSparepart::pluck('id')->toArray();

        $spareparts = [
            [
                'kode_sparepart' => 'SPRT001',
                'nama' => 'Busi NGK',
                'deskripsi' => 'Busi NGK kualitas tinggi untuk motor harian',
                'stok' => 50,
                'harga_beli' => 10000,
                'harga_jual' => 15000,
                'merk' => 'NGK',
                'gambar_produk' => '',
            ],
            [
                'kode_sparepart' => 'SPRT002',
                'nama' => 'Oli Mesin Shell',
                'deskripsi' => 'Oli pelumas mesin motor 10W-40',
                'stok' => 30,
                'harga_beli' => 40000,
                'harga_jual' => 55000,
                'merk' => 'Shell',
                'gambar_produk' => '',
            ],
            [
                'kode_sparepart' => 'SPRT003',
                'nama' => 'Lampu LED',
                'deskripsi' => 'Lampu LED putih terang hemat daya',
                'stok' => 20,
                'harga_beli' => 20000,
                'harga_jual' => 30000,
                'merk' => 'Philips',
                'gambar_produk' => '',
            ],
        ];

        foreach ($spareparts as $item) {
            $item['kategori_id'] = $kategoriIds[array_rand($kategoriIds)];
            Sparepart::create($item);
        }
    }
}
