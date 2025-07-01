<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    use HasFactory;

    protected $table = 'tb_sparepart';
    protected $primaryKey = 'kode_sparepart';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_sparepart',
        'kategori_id',
        'nama',
        'deskripsi',
        'stok',
        'harga_beli',
        'harga_jual',
        'merk',
        'gambar_produk',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriSparepart::class, 'kategori_id');
    }

    public function komentar()
    {
        return $this->hasMany(Komentar::class, 'sparepart_id');
    }
}
