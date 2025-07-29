<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServisMotor extends Model
{
    use HasFactory;

    protected $table = 'tb_servismotor';

    protected $fillable = [
        'user_id',
        'no_kendaraan',
        'jenis_motor',
        'keluhan',
        'status',
        'catatan_admin',
    ];

    protected $casts = [
        'user_id' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
