<?php

use App\Models\Rfid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\RfidController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    }
    return redirect('/admin/login');
});

// Group prefix 'rfid' untuk rute-rute terkait
Route::prefix('rfid')->group(function () {

    // 📥 ESP32: Endpoint POST saat kartu discan (mode register / access)
    Route::post('/scan', [RfidController::class, 'scan'])->name('api.rfid.scan');

    // ✅ ESP32: Endpoint polling tag_id terbaru yang siap diregistrasi
    Route::get('/pending-tag', [RfidController::class, 'getPendingTag']);

    // 🛠️ Admin: Update dan hapus kartu
    Route::put('/{tag_id}', [RfidController::class, 'update']);
    Route::delete('/{tag_id}', [RfidController::class, 'destroy']);


    // 📋 Admin: List semua kartu RFID
    Route::get('/{tag_id}', [RfidController::class, 'show']);

    // 📦 Dapatkan RFID terakhir yang masuk ke database
    Route::get('/latest-rfid', function () {
        $latest = Rfid::orderBy('created_at', 'desc')->first();
        return response()->json(['tag_id' => $latest->tag_id ?? null]);
    });

    // 📈 Ringkasan statistik RFID
    Route::get('/stats/summary', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'total_rfids' => Rfid::count(),
                // 👇 komentar/disable jika belum punya model scan
                // 'total_scans' => RfidScan::count(),
                // 'scans_today' => RfidScan::whereDate('created_at', today())->count(),
                // 'latest_scan' => RfidScan::with('rfid')->latest()->first(),
            ],
        ]);
    });
});

// 🌐 Fallback jika endpoint tidak ditemukan
Route::fallback(fn() => response()->json([
    'success' => false,
    'message' => 'API endpoint tidak ditemukan.',
], 404));
