<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $table = 'tb_reviews';

    protected $fillable = [
        'sparepart_id',
        'user_id',
        'rating',
        'komentar',
    ];

    /**
     * Relasi ke model Sparepart
     */
    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class, 'sparepart_id', 'kode_sparepart');
    }

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
