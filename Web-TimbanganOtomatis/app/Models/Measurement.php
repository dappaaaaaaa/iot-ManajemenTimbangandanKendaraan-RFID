<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Measurement extends Model
{
    use HasFactory;

    // ✅ Tambahkan ini
    public bool $bypassObserver = false;

    protected $fillable = [
        'vehicle_number',
        'gross_at_mine',
        'tare_at_mine',
        'gross_at_jetty',
        'tare_at_jetty',
        'jetty_entry_time',
        'attachments',
        'is_pending',
        'is_approved',
        'approved_by',
        'rejected_by',
        'edited_by',
        'updated_by',
        'pending_changes',
        'user_id', // pastikan field ini ada di tabel jika digunakan
    ];

    protected $casts = [
	'id' => 'string',
        'jetty_entry_time' => 'datetime',
        //'attachments' => 'array',
        'pending_changes' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'is_pending' => 'boolean',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];



    /*
    |--------------------------------------------------------------------------
    | Accessor & Mutator: attachments
    |--------------------------------------------------------------------------
    */
/*
    public function getAttachmentsAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) ?: [] : ($value ?? []);
    }

    public function setAttachmentsAttribute($value)
    {
        $this->attributes['attachments'] = is_array($value)
            ? json_encode($value)
            : ($value ?? json_encode([]));
    }
 */
    /*
    |--------------------------------------------------------------------------
    | Relasi ke User
    |--------------------------------------------------------------------------
    */

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi ke MeasurementHistory
    |--------------------------------------------------------------------------
    */


public function histories()
{
    $uuidId = (string) $this->id;

    return $this->hasMany(MeasurementHistory::class, 'measurement_id', 'id')
        ->whereRaw('measurement_histories.measurement_id::text = ?', [$uuidId]);
}



    /*
    |--------------------------------------------------------------------------
    | Method Tambahan
    |--------------------------------------------------------------------------
    */

    /**
     * Mengecek apakah data boleh diedit bulan ini.
     */
    public function isEditableThisMonth(): bool
    {
        return optional($this->created_at)->isSameMonth(now());
    }

    /**
     * Properti sementara untuk menyimpan data histori sebelum update.
     * Ini tidak disimpan ke database.
     */
    public array $temp_history_payload = [];
}
