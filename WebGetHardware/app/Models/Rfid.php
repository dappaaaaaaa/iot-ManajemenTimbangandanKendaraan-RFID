<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rfid extends Model
{
    protected $table = 'rfids';

    protected $fillable = [
        'tag_id',
        'owner_name',
        'vehicle_number',
        'scanned_at',
        'status',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    // Default status
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Boot model hooks.
     */
    protected static function booted()
    {
        // Jika data lengkap, ubah status jadi 'active'
        static::saving(function ($rfid) {
            if (!empty($rfid->owner_name) && !empty($rfid->vehicle_number)) {
                $rfid->status = 'active';
            }

            // Jika scanned_at belum ada, set otomatis
            if (empty($rfid->scanned_at)) {
                $rfid->scanned_at = now();
            }
        });
    }
}
