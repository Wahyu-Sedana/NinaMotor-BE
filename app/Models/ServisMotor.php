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
        'harga_servis',
        'transaksi_id',
    ];

    protected $casts = [
        'user_id' => 'string',
        'harga_servis' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_SERVICE = 'in_service';
    const STATUS_DONE = 'done';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id', 'id');
    }

    public function hasPrice()
    {
        return !is_null($this->harga_servis) && $this->harga_servis > 0;
    }

    public function hasTransaction()
    {
        return !is_null($this->transaksi_id);
    }
}
