<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Measurement;
use App\Models\MeasurementHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MeasurementController extends Controller
{
    /**
     * Display a listing of the measurements.
     */
    public function index(Request $request)
    {
        $query = Measurement::with(['user', 'editor', 'updatedBy']);

        // Filter berdasarkan status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->pending();
                    break;
                case 'approved':
                    $query->approved();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
            }
        }

        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->where('jetty_entry_time', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('jetty_entry_time', '<=', $request->date_to . ' 23:59:59');
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_number', 'ILIKE', "%{$search}%")
                    ->orWhere('vendor', 'ILIKE', "%{$search}%")
                    ->orWhere('nama_barang', 'ILIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'jetty_entry_time');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $measurements = $query->paginate($perPage);

        // Jika request AJAX, return JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $measurements,
                'html' => view('measurements.table', compact('measurements'))->render()
            ]);
        }

        return view('measurements.index', compact('measurements'));
    }

    /**
     * Show the form for creating a new measurement.
     */
    public function create()
    {
        return view('measurements.create');
    }

    /**
     * Store a newly created measurement in storage.
     */
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
            'vendor' => 'nullable|string|max:100',
            'nama_barang' => 'nullable|string|max:100',
            'kode_no_dermaga' => 'nullable|string|max:50',
            'kode_no_tambang' => 'nullable|string|max:50',
            'pengirim' => 'nullable|string|max:100',
            'penerima' => 'nullable|string|max:100',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['attachments']);

            // Handle file uploads
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

            return response()->json([
                'success' => true,
                'message' => 'Data measurement berhasil disimpan',
                'data' => $measurement->load(['user', 'editor'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified measurement.
     */
    public function show($id)
    {
        $measurement = Measurement::with(['user', 'editor', 'updatedBy', 'histories.user'])
            ->findOrFail($id);

        return view('measurements.show', compact('measurement'));
    }

    /**
     * Show the form for editing the specified measurement.
     */
    public function edit($id)
    {
        $measurement = Measurement::findOrFail($id);

        // Check authorization (optional)
        // $this->authorize('update', $measurement);

        return view('measurements.edit', compact('measurement'));
    }

    /**
     * Update the specified measurement in storage.
     */
    public function update(Request $request, $id)
    {
        $measurement = Measurement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vehicle_number' => 'required|string|max:50',
            'gross_at_mine' => 'nullable|numeric|min:0',
            'tare_at_mine' => 'nullable|numeric|min:0',
            'gross_at_jetty' => 'nullable|numeric|min:0',
            'tare_at_jetty' => 'nullable|numeric|min:0',
            'mine_entry_time' => 'nullable|date',
            'jetty_entry_time' => 'nullable|date',
            'vendor' => 'nullable|string|max:100',
            'nama_barang' => 'nullable|string|max:100',
            'kode_no_dermaga' => 'nullable|string|max:50',
            'kode_no_tambang' => 'nullable|string|max:50',
            'pengirim' => 'nullable|string|max:100',
            'penerima' => 'nullable|string|max:100',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['attachments']);

            // Handle file uploads
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

            return response()->json([
                'success' => true,
                'message' => 'Data measurement berhasil diupdate',
                'data' => $measurement->fresh(['user', 'editor'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified measurement from storage.
     */
    public function destroy($id)
    {
        $measurement = Measurement::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete attachments if exist
            if ($measurement->attachments) {
                foreach ($measurement->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                }
            }

            $measurement->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data measurement berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get history of specific measurement
     */
    public function history($id)
    {
        $measurement = Measurement::with(['histories.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'measurement' => $measurement->only(['id', 'vehicle_number']),
                'histories' => $measurement->histories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'action' => $history->action_text,
                        'user' => $history->user->name ?? 'System',
                        'date' => $history->formatted_date,
                        'time_ago' => $history->time_ago,
                        'description' => $history->description,
                        'changes_summary' => $history->getChangesSummary(),
                        'ip_address' => $history->ip_address,
                    ];
                })
            ]
        ]);
    }

    /**
     * Approve measurement
     */
    public function approve($id)
    {
        $measurement = Measurement::findOrFail($id);

        if ($measurement->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Data sudah disetujui sebelumnya'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $measurement->approveBy(Auth::id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disetujui',
                'data' => $measurement->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject measurement
     */
    public function reject($id)
    {
        $measurement = Measurement::findOrFail($id);

        if ($measurement->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Data sudah ditolak sebelumnya'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $measurement->rejectBy(Auth::id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditolak',
                'data' => $measurement->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set measurement as pending
     */
    public function setPending($id)
    {
        $measurement = Measurement::findOrFail($id);

        if ($measurement->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Data sudah dalam status pending'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $measurement->setPending();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diset ke status pending',
                'data' => $measurement->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get measurements statistics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $stats = [
            'total' => Measurement::byDateRange($dateFrom, $dateTo)->count(),
            'approved' => Measurement::approved()->byDateRange($dateFrom, $dateTo)->count(),
            'pending' => Measurement::pending()->byDateRange($dateFrom, $dateTo)->count(),
            'rejected' => Measurement::rejected()->byDateRange($dateFrom, $dateTo)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Export measurements to Excel/CSV
     */
    public function export(Request $request)
    {
        // Implementation for export functionality
        // You can use Laravel Excel package for this

        return response()->json([
            'success' => true,
            'message' => 'Export functionality will be implemented'
        ]);
    }

    /**
     * Bulk operations (approve, reject, delete)
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:measurements,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
$ids = collect($request->ids)
    ->filter(fn($id) => Str::isUuid($id))
    ->values()
    ->toArray();

$measurements = Measurement::whereIn('id', collect($ids)->filter()->map(fn($id) => (string) $id)->toArray())->get();

            $successCount = 0;

            foreach ($measurements as $measurement) {
                switch ($request->action) {
                    case 'approve':
                        if (!$measurement->isApproved()) {
                            $measurement->approveBy(Auth::id());
                            $successCount++;
                        }
                        break;
                    case 'reject':
                        if (!$measurement->isRejected()) {
                            $measurement->rejectBy(Auth::id());
                            $successCount++;
                        }
                        break;
                    case 'delete':
                        $measurement->delete();
                        $successCount++;
                        break;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} data berhasil di{$request->action}"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($id, $attachmentIndex)
    {
        $measurement = Measurement::findOrFail($id);

        if (!isset($measurement->attachments[$attachmentIndex])) {
            abort(404, 'Attachment not found');
        }

        $attachment = $measurement->attachments[$attachmentIndex];
        $filePath = storage_path('app/public/' . $attachment['path']);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $attachment['filename']);
    }
}
