<?php

namespace App\Models;

use App\Models\User;
use App\Models\Rfid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Measurement extends Model
{
    use HasFactory;

    protected $table = 'measurements'; // nama tabel
    protected $primaryKey = 'id';      // ganti kalau pakai nama lain
    public $incrementing = true;       // false kalau bukan auto increment
    protected $keyType = 'int';        // string kalau UUID

    protected $fillable = [
        'vehicle_number',
        'gross_at_mine',
        'tare_at_mine',
        'gross_at_jetty',
        'tare_at_jetty',
        'jetty_entry_time',
        'last_tap_stage',
        'user_id', // pastikan field ini ada di tabel jika digunakan
        'owner_name',
        'tag_id',
        'is_manual_gross_mine',
        'is_manual_tare_mine',
        'is_manual_gross_jetty',
        'is_manual_tare_jetty',
    ];

    protected $casts = [
        'id' => 'string',
        'jetty_entry_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_manual_gross_mine' => 'boolean',
        'is_manual_tare_mine' => 'boolean',
        'is_manual_gross_jetty' => 'boolean',
        'is_manual_tare_jetty' => 'boolean',
    ];

    protected $attributes = [
        'is_manual_gross_mine' => false,
        'is_manual_tare_mine' => false,
        'is_manual_gross_jetty' => false,
        'is_manual_tare_jetty' => false,
    ];


    /*
    |--------------------------------------------------------------------------
    | Relasi ke User
    |--------------------------------------------------------------------------
    */
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

    protected static function booted()
    {
        static::saving(function ($measurement) {
            if ($measurement->tag_id) {
                $rfid = Rfid::where('tag_id', $measurement->tag_id)->first();
                if ($rfid) {
                    $measurement->owner_name = $rfid->owner_name;
                }
            }
        });
    }

    public function openGate(): void
    {
        try {
            $response = Http::timeout(5)->post('http://10.111.172.81:8000/gate/open', [
                'measurement_id' => $this->id,
                'vehicle_number' => $this->vehicle_number,
            ]);

            if ($response->failed()) {
                throw new \Exception("API gagal: " . $response->body());
            }

            Log::info("Gate berhasil dibuka", [
                'measurement_id' => $this->id,
                'response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error("Gagal membuka gate", [
                'measurement_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    // Hitung berat bersih di tambang
    public function getNetAtMineAttribute(): float
    {
        return (float) $this->gross_at_mine - (float) $this->tare_at_mine;
    }

    // Hitung berat bersih di jetty
    public function getNetAtJettyAttribute(): float
    {
        return (float) $this->gross_at_jetty - (float) $this->tare_at_jetty;
    }

    public function rfid(): BelongsTo
    {
        return $this->belongsTo(Rfid::class, 'tag_id', 'id');
    }
}
