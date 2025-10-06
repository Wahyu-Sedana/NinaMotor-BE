<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlamatPengiriman extends Model
{
    protected $table = 'tb_alamat_pengiriman';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'label_alamat',
        'nama_penerima',
        'no_telp_penerima',
        'alamat_lengkap',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'kode_pos',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'district_id' => 'integer'
    ];

    /**
     * Relationship dengan User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship dengan Transaksi
     */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'alamat_id');
    }
}
