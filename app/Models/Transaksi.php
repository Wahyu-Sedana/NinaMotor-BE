<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'tb_transaksi';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'tanggal_transaksi',
        'total',
        'metode_pembayaran',
        'status_pembayaran',
        'snap_token',
        'items_data',
        'type_pembelian',
        'alamat_id',
        'ongkir',
        'kurir',
        'service',
        'estimasi',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'berhasil';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'gagal';
    const STATUS_EXPIRED = 'expired';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (empty($model->tanggal_transaksi)) {
                $model->tanggal_transaksi = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function servisMotor()
    {
        return $this->hasOne(ServisMotor::class, 'transaksi_id', 'id');
    }

    public function alamatPengiriman()
    {
        return $this->belongsTo(AlamatPengiriman::class, 'alamat_id', 'id');
    }
}
