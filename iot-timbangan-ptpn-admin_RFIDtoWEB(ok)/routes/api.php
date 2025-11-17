<?php

use App\Models\Rfid;
use App\Models\RfidScan;
use App\Models\PendingRfidTag;
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


Route::prefix('rfid')->group(function () {
    Route::post('/scan', [RfidController::class, 'scan']);
    Route::get('/latest-scan', [RfidController::class, 'getLatestScan']); // ✅ benar
});



// 🌐 Fallback jika endpoint tidak ditemukan
Route::fallback(fn() => response()->json([
    'success' => false,
    'message' => 'API endpoint tidak ditemukan.',
], 404));
