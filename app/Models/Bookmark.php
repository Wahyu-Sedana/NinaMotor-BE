<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $table = 'tb_bookmark';

    protected $fillable = [
        'sparepart_id',
        'user_id',
    ];

    /**
     * Relasi ke Sparepart
     */
    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class, 'sparepart_id');
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
