<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeasurementHistory extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'measurement_histories';

    // Field yang boleh diisi secara massal
    protected $fillable = [
        'measurement_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'changed_fields',
        'description',
        'action_note',
    ];

    protected $casts = [
	'measurement_id' => 'string',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Tambahan
    |--------------------------------------------------------------------------
    */

    // Scope untuk filter approval (disetujui/ditolak)
    public function scopeApprovals($query)
    {
        return $query->whereIn('action', ['approved_update', 'rejected_update']);
    }

    // Scope untuk filter update permintaan
    public function scopeRequests($query)
    {
        return $query->where('action', 'requested_update');
    }

    // Scope untuk filter berdasarkan measurement_id
    public function scopeByMeasurement($query, $measurementId)
    {
return $query->where('measurement_id', (string) $measurementId);
    }

    /*
    |--------------------------------------------------------------------------
    | Atribut Tambahan
    |--------------------------------------------------------------------------
    */

    // Tampilkan nama user dengan fallback jika tidak ada
    public function getUsernameAttribute()
    {
        return $this->user?->name ?? 'Tidak diketahui';
    }

    // Format label aksi untuk ditampilkan di UI
    public function getFormattedActionAttribute()
    {
        return match ($this->action) {
            'created' => 'Dibuat',
            'requested_update' => 'Pengajuan Perubahan',
            'approved_update' => 'Perubahan Disetujui',
            'rejected_update' => 'Perubahan Ditolak',
            'deleted' => 'Dihapus',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
