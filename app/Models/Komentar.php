<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Komentar extends Model
{
    use HasFactory;

    protected $table = 'tb_komentar';

    protected $fillable = [
        'user_id',
        'sparepart_id',
        'isi_komentar',
        'tanggal_komentar',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class, 'sparepart_id');
    }
}
