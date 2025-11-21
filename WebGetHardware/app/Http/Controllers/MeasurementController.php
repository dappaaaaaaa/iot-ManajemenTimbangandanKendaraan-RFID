<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Measurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MeasurementController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_number' => 'required|string|max:50',
            'gross_at_mine' => 'nullable|numeric|min:0',
            'tare_at_mine' => 'nullable|numeric|min:0',
            'gross_at_jetty' => 'nullable|numeric|min:0',
            'tare_at_jetty' => 'nullable|numeric|min:0',
            'mine_entry_time' => 'nullable|date',
            'jetty_entry_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['attachments']);

            // Tandai data yang diisi manual
            if ($request->filled('gross_at_mine')) $data['is_manual_gross_mine'] = true;
            if ($request->filled('gross_at_jetty')) $data['is_manual_gross_jetty'] = true;
            if ($request->filled('tare_at_jetty')) $data['is_manual_tare_jetty'] = true;

            // Upload lampiran
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('measurements', 'public');
                    $attachments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                    ];
                }
                $data['attachments'] = $attachments;
            }

            $measurement = Measurement::create($data);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data measurement berhasil disimpan', 'data' => $measurement]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $measurement = Measurement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_number' => 'required|string|max:50',
            'gross_at_mine' => 'nullable|numeric|min:0',
            'gross_at_jetty' => 'nullable|numeric|min:0',
            'tare_at_jetty' => 'nullable|numeric|min:0',
            'mine_entry_time' => 'nullable|date',
            'jetty_entry_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['attachments']);

            // Tandai data manual
            if ($request->filled('gross_at_mine')) $data['is_manual_gross_mine'] = true;
            if ($request->filled('gross_at_jetty')) $data['is_manual_gross_jetty'] = true;
            if ($request->filled('tare_at_jetty')) $data['is_manual_tare_jetty'] = true;

            // Upload lampiran
            if ($request->hasFile('attachments')) {
                $attachments = $measurement->attachments ?? [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('measurements', 'public');
                    $attachments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                    ];
                }
                $data['attachments'] = $attachments;
            }

            $measurement->update($data);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data measurement berhasil diupdate', 'data' => $measurement]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
