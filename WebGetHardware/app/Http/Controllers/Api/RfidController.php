<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            'mode' => 'required|in:register,check',
        ]);

        if ($validated['mode'] === 'register') {
            // Simpan tag_id ke cache agar bisa diambil frontend (live fill)
            Cache::put('rfid_latest_scan', $validated['tag_id'], now()->addSeconds(10));

            return response()->json([
                'success' => true,
                'message' => 'Tag ID berhasil direkam',
            ]);
        }
    }

    /**
     * Endpoint GET: /api/rfid/latest-scan
     * Dipanggil oleh frontend (polling)
     */
    public function getLatestScan()
    {
        $tagId = Cache::get('rfid_latest_scan');

        return response()->json([
            'success' => $tagId ? true : false,
            'tag_id' => $tagId,
        ]);
    }
}
