<?php

// app/Models/PendingRfidTag.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRfidTag extends Model
{
    protected $table = 'rfid_temp_scans'; // ini pastikan cocok dengan nama tabel
    protected $fillable = ['tag_id', 'scanned_at'];
    public $timestamps = false;
}
