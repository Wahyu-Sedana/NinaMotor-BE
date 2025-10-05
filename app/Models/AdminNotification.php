<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'title',
        'message',
        'data',
        'action_url',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array', // Otomatis convert JSON ke array
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Polymorphic relationship - bisa ke ServisMotor, Transaksi, Order, dll
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Scope untuk notifikasi yang belum dibaca
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope untuk notifikasi yang sudah dibaca
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope berdasarkan type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark notifikasi sebagai sudah dibaca
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Helper untuk format data notifikasi
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->locale('id')->diffForHumans();
    }
}
