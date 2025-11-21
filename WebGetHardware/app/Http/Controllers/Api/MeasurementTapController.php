<?php

namespace App\Http\Controllers\Api;

use App\Models\Rfid;
use App\Models\Measurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MeasurementTapController extends Controller
{
    public function handleTap(Request $request)
    {
        $request->validate([
            'tag_id' => 'required|string',
            'weight' => 'required|numeric|min:0',
        ]);

        $rfid = Rfid::where('tag_id', $request->tag_id)->first();
        if (!$rfid) {
            return response()->json(['error' => 'RFID tidak terdaftar'], 404);
        }

        $weight = $request->weight;

        // Cari measurement aktif (belum completed)
        $activeMeasurement = Measurement::where('vehicle_number', $rfid->vehicle_number)
            ->where('measurement_status', '!=', 'completed')
            ->latest()
            ->first();

        // Cek apakah ada measurement completed yang baru saja selesai
        $lastCompletedMeasurement = Measurement::where('vehicle_number', $rfid->vehicle_number)
            ->where('measurement_status', 'completed')
            ->latest()
            ->first();

        /**
         * LOGIKA BARU: Cek apakah measurement terakhir sudah lengkap
         * Jika ya, izinkan membuat measurement baru meskipun RFID sama
         */
        $canCreateNew = $this->canCreateNewMeasurement($activeMeasurement, $lastCompletedMeasurement);

        /**
         * Jika tidak ada measurement aktif ATAU measurement terakhir sudah lengkap,
         * buat measurement baru (tap 1)
         */
        if (!$activeMeasurement || $canCreateNew) {
            // Jika ada measurement aktif tapi sudah lengkap, tandai sebagai completed
            if ($activeMeasurement && $this->isMeasurementComplete($activeMeasurement)) {
                $activeMeasurement->update(['measurement_status' => 'completed']);
                Log::info("Measurement ID {$activeMeasurement->id} ditandai sebagai completed");
            }

            $measurement = Measurement::create([
                'vehicle_number' => $rfid->vehicle_number,
                'gross_at_mine' => $weight,
                'mine_entry_time' => now(),
                'measurement_status' => 'on_going',
                'last_tap_stage' => 'gross_at_mine',
                'user_id' => auth()->id() ?? null,
            ]);

            $this->openGate($rfid);

            Log::info("Measurement baru dibuat untuk kendaraan {$rfid->vehicle_number} dengan RFID {$rfid->tag_id}");

            return response()->json([
                'message' => 'Gross di Tambang tersimpan - Data baru dimulai',
                'stage' => 'gross_at_mine',
                'gate_open' => true,
                'data' => $measurement
            ]);
        }

        /**
         * Jika ada measurement aktif yang belum lengkap, lanjutkan proses
         */
        $measurement = $activeMeasurement;

        /**
         * Tentukan tahap berikutnya
         */
        $nextStage = $this->getNextStage($measurement);

        // Jika gross_at_mine sudah manual, lompat ke gross_jetty
        if ($measurement->is_manual_gross_mine && $measurement->last_tap_stage === null) {
            $nextStage = 'gross_at_jetty';
        }

        if (!$nextStage) {
            // Mark measurement as completed jika semua tahap sudah lengkap
            $measurement->update(['measurement_status' => 'completed']);

            return response()->json([
                'message' => 'Semua tahap timbang sudah lengkap.',
                'stage' => $measurement->last_tap_stage,
                'gate_open' => false,
                'data' => $measurement,
                'measurement_complete' => true // Flag untuk Arduino
            ]);
        }

        // Update sesuai tahap
        $updateData = [
            $nextStage => $weight,
            'last_tap_stage' => $nextStage,
        ];

        if ($nextStage === 'gross_at_jetty') {
            $updateData['jetty_entry_time'] = now();
            $updateData['measurement_status'] = 'at_jetty';
        }

        if ($nextStage === 'tare_at_jetty') {
            $updateData['measurement_status'] = 'completed';
            Log::info("Measurement ID {$measurement->id} selesai - semua data lengkap");
        }

        $measurement->update($updateData);

        $this->openGate($rfid);

        // Cek apakah measurement sekarang sudah lengkap
        $isComplete = $this->isMeasurementComplete($measurement->fresh());
        $message = ucfirst(str_replace('_', ' ', $nextStage)) . ' tersimpan';

        if ($isComplete) {
            $message .= ' - Proses timbang selesai';
        }

        return response()->json([
            'message' => $message,
            'stage' => $nextStage,
            'gate_open' => true,
            'data' => $measurement->fresh(),
            'measurement_complete' => $isComplete
        ]);
    }

    /**
     * Menentukan tahap selanjutnya berdasarkan data measurement.
     */
    private function getNextStage(Measurement $measurement): ?string
    {
        if (empty($measurement->gross_at_mine) && !$measurement->is_manual_gross_mine) {
            return 'gross_at_mine';
        }

        if (empty($measurement->gross_at_jetty)) {
            return 'gross_at_jetty';
        }

        if (empty($measurement->tare_at_jetty)) {
            return 'tare_at_jetty';
        }

        return null;
    }

    /**
     * Cek apakah measurement sudah lengkap (semua data penting terisi)
     */
    private function isMeasurementComplete(Measurement $measurement): bool
    {
        $hasGrossMine = !empty($measurement->gross_at_mine) || $measurement->is_manual_gross_mine;
        $hasGrossJetty = !empty($measurement->gross_at_jetty);
        $hasTareJetty = !empty($measurement->tare_at_jetty);

        $isComplete = $hasGrossMine && $hasGrossJetty && $hasTareJetty;

        Log::info("Pengecekan kelengkapan measurement ID {$measurement->id}:", [
            'gross_mine' => $hasGrossMine ? 'OK' : 'KOSONG',
            'gross_jetty' => $hasGrossJetty ? 'OK' : 'KOSONG',
            'tare_jetty' => $hasTareJetty ? 'OK' : 'KOSONG',
            'is_complete' => $isComplete ? 'YA' : 'TIDAK'
        ]);

        return $isComplete;
    }

    /**
     * Cek apakah bisa membuat measurement baru
     * Berdasarkan status measurement yang ada
     */
    private function canCreateNewMeasurement($activeMeasurement, $lastCompletedMeasurement): bool
    {
        // Jika tidak ada measurement aktif, pasti bisa buat baru
        if (!$activeMeasurement) {
            return true;
        }

        // Jika measurement aktif sudah lengkap datanya, bisa buat baru
        if ($this->isMeasurementComplete($activeMeasurement)) {
            Log::info("Measurement aktif sudah lengkap, membolehkan data baru");
            return true;
        }

        // Jika ada measurement yang baru completed dalam 1 jam terakhir
        // dan measurement aktif masih kosong, bisa jadi perlu reset
        if (
            $lastCompletedMeasurement &&
            $lastCompletedMeasurement->updated_at->diffInHours(now()) <= 1 &&
            empty($activeMeasurement->gross_at_mine) &&
            empty($activeMeasurement->gross_at_jetty) &&
            empty($activeMeasurement->tare_at_jetty)
        ) {

            Log::info("Measurement completed baru ada, reset measurement kosong");
            return true;
        }

        return false;
    }

    /**
     * Simulasikan pembukaan palang (servo)
     */
    private function openGate($rfid)
    {
        Log::info("Gate dibuka untuk kendaraan dengan RFID: {$rfid->tag_id}");
    }

    /**
     * Method untuk mendapatkan status measurement terakhir
     * Berguna untuk debugging dan monitoring
     */
    public function getMeasurementStatus(Request $request)
    {
        $request->validate([
            'tag_id' => 'required|string',
        ]);

        $rfid = Rfid::where('tag_id', $request->tag_id)->first();
        if (!$rfid) {
            return response()->json(['error' => 'RFID tidak terdaftar'], 404);
        }

        $activeMeasurement = Measurement::where('vehicle_number', $rfid->vehicle_number)
            ->where('measurement_status', '!=', 'completed')
            ->latest()
            ->first();

        $lastCompletedMeasurement = Measurement::where('vehicle_number', $rfid->vehicle_number)
            ->where('measurement_status', 'completed')
            ->latest()
            ->first();

        return response()->json([
            'vehicle_number' => $rfid->vehicle_number,
            'active_measurement' => $activeMeasurement,
            'last_completed_measurement' => $lastCompletedMeasurement,
            'can_create_new' => $this->canCreateNewMeasurement($activeMeasurement, $lastCompletedMeasurement),
            'active_is_complete' => $activeMeasurement ? $this->isMeasurementComplete($activeMeasurement) : null
        ]);
    }
}
