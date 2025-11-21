<?php

use App\Models\Rfid;
use App\Models\RfidScan;
use App\Models\PendingRfidTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\RfidController;
use App\Http\Controllers\Api\MeasurementTapController;

Route::post('/measurement/tap', [MeasurementTapController::class, 'handleTap']);

Route::post('/gate/open', function () {
    // Panggil logika buka servo
    return response()->json(['status' => 'Gate opened']);
});


Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    }
    return redirect('/admin/login');
});


Route::prefix('rfid')->group(function () {
    Route::post('/scan', [RfidController::class, 'scan']);
    Route::get('/latest-scan', [RfidController::class, 'getLatestScan']);
});



// Fallback jika endpoint tidak ditemukan
Route::fallback(fn() => response()->json([
    'success' => false,
    'message' => 'API endpoint tidak ditemukan.',
], 404));
