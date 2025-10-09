<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriSparepart extends Model
{
    use HasFactory;

    protected $table = 'tb_kategori_sparepart';
    protected $primaryKey = 'id';   

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
    ];

    public function spareparts()
    {
        return $this->hasMany(Sparepart::class, 'kategori_id');
    }
}
