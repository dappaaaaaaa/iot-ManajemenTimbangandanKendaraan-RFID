<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RfidController extends Controller
{
    /**
     * Endpoint POST: /api/rfid/scan
     * Dipanggil dari ESP32
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'tag_id' => 'required|string',
        ]);

        $tagId = strtoupper(trim($validated['tag_id'])); // Normalisasi format

        // Cek apakah tag sudah terdaftar di DB
        $rfid = \App\Models\Rfid::where('tag_id', $tagId)->first();

        if ($rfid) {
            // Sudah ada → mode timbang/check
            $mode = 'check';

            Log::info("RFID Scan - Existing card: {$tagId}", [
                'owner' => $rfid->owner_name,
                'vehicle' => $rfid->vehicle_number
            ]);
        } else {
            // Belum ada → mode daftar/register
            $mode = 'register';

            Log::info("RFID Scan - New card detected: {$tagId}");
        }

        // Simpan ke cache dengan timestamp untuk frontend
        $cacheData = [
            'tag_id' => $tagId,
            'mode' => $mode,
            'timestamp' => now()->timestamp,
            'owner_name' => $rfid->owner_name ?? null,
            'vehicle_number' => $rfid->vehicle_number ?? null
        ];

        Cache::put('rfid_latest_scan', $cacheData, now()->addSeconds(30)); // Extend to 30 seconds

        // Juga simpan dengan key spesifik untuk debugging
        Cache::put("rfid_scan_{$tagId}", $cacheData, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'mode'    => $mode,
            'tag_id'  => $tagId,
            'owner_name' => $rfid->owner_name ?? null,
            'vehicle_number' => $rfid->vehicle_number ?? null,
            'message' => $mode === 'register' ? 'Kartu baru, silakan daftarkan' : 'Kartu terdaftar, siap timbang'
        ]);
    }

    /**
     * Endpoint GET: /api/rfid/latest-scan
     * Dipanggil oleh frontend (polling)
     */
    public function getLatestScan()
    {
        $cacheData = Cache::get('rfid_latest_scan');

        if ($cacheData) {
            Log::info("RFID Latest Scan - Data found", $cacheData);

            return response()->json([
                'success' => true,
                'tag_id' => $cacheData['tag_id'],
                'mode' => $cacheData['mode'],
                'timestamp' => $cacheData['timestamp'],
                'owner_name' => $cacheData['owner_name'] ?? null,
                'vehicle_number' => $cacheData['vehicle_number'] ?? null
            ]);
        }

        return response()->json([
            'success' => false,
            'tag_id' => null,
            'message' => 'No recent RFID scan'
        ]);
    }

    /**
     * Endpoint untuk clear cache (optional, untuk debugging)
     */
    public function clearCache()
    {
        Cache::forget('rfid_latest_scan');

        return response()->json([
            'success' => true,
            'message' => 'RFID cache cleared'
        ]);
    }

    /**
     * Endpoint untuk test scan (optional, untuk testing tanpa ESP32)
     */
    public function testScan(Request $request)
    {
        $testTagId = $request->get('tag_id', 'TEST123456');

        return $this->scan(new Request(['tag_id' => $testTagId]));
    }
}
