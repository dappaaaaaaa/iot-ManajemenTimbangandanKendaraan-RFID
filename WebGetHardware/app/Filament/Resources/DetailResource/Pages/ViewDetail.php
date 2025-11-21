<?php

namespace App\Filament\Resources\DetailResource\Pages;

use App\Filament\Resources\DetailResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DetailExport;

class ViewDetail extends Page
{
    protected static string $resource = DetailResource::class;
    protected static string $view = 'filament.resources.detail-resource.pages.view-detail';

    // data
    public $record;
    public $details;
    public $totalNetto;

    // filter tanggal (bind ke query string agar persistence / tersedia)
    public $from = null;
    public $to = null;

    // biar Livewire/Filament otomatis sinkron dengan query string ?from=...&to=...
    protected $queryString = [
        'from' => ['except' => null],
        'to'   => ['except' => null],
    ];

    public function getTitle(): string
    {
        return 'Detail Angkutan - ' . ($this->record->tag_id ?? '');
    }

    public function mount($record)
    {
        // simpan header
        $this->record = $this->fetchRecord($record);

        // baca filter dari request jika belum di-bind (mount dipanggil setiap load)
        $this->from = $this->from ?? request('from');
        $this->to   = $this->to   ?? request('to');

        // panggil helper dengan filter
        $this->details    = $this->fetchDetails($record, $this->from, $this->to);
        $this->totalNetto = $this->fetchTotalNetto($record, $this->from, $this->to);
    }

    // ====== EXPORTS ======
    public function exportPdf(string $tag)
    {
        $header     = $this->fetchRecord($tag);
        $details    = $this->fetchDetails($tag);
        $totalNetto = $this->fetchTotalNetto($tag);

        $from = request('from');
        $to   = request('to');

        // Format periode
        $periode = null;
        if ($from && $to) {
            $periode = \Carbon\Carbon::parse($from)->translatedFormat('d F Y') . ' - ' .
                \Carbon\Carbon::parse($to)->translatedFormat('d F Y');
        } elseif ($from) {
            $periode = 'Mulai ' . \Carbon\Carbon::parse($from)->translatedFormat('d F Y');
        } elseif ($to) {
            $periode = 'Sampai ' . \Carbon\Carbon::parse($to)->translatedFormat('d F Y');
        }

        $pdf = Pdf::loadView(
            'exports.detail-pdf',
            compact('header', 'details', 'totalNetto', 'periode')
        );

        return $pdf->download("Detail_Angkutan_{$tag}.pdf");
    }


    public function exportExcel(string $tag)
    {
        $from = $this->from ?? request('from');
        $to   = $this->to   ?? request('to');

        return Excel::download(new DetailExport($tag, $from, $to), "Detail_Angkutan_{$tag}.xlsx");
    }

    // ====== HELPERS ======
    protected function fetchRecord(string $tag)
    {
        return DB::table('measurements')
            ->join('rfids', 'rfids.tag_id', '=', 'measurements.tag_id')
            ->where('measurements.tag_id', $tag)
            ->select('rfids.vehicle_number', 'rfids.owner_name', 'measurements.tag_id')
            ->first();
    }

    /**
     * Ambil detail dengan optional filter from/to (YYYY-MM-DD)
     */
    protected function fetchDetails(string $tag, $from = null, $to = null)
    {
        $query = DB::table('measurements')
            ->where('measurements.tag_id', $tag)
            ->selectRaw("
                DATE(measurements.created_at) as tanggal,
                TO_CHAR(measurements.created_at, 'HH24:MI') as jam,
                ROUND(measurements.gross_at_jetty - measurements.tare_at_jetty, 2) as netto
            ");

        if ($from) {
            // pastikan format YYYY-MM-DD, whereDate aman dipakai
            $query->whereDate('measurements.created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('measurements.created_at', '<=', $to);
        }

        return $query->orderBy('measurements.created_at', 'asc')->get();
    }

    /**
     * Hitung total netto dengan optional filter
     */
    protected function fetchTotalNetto(string $tag, $from = null, $to = null)
    {
        $query = DB::table('measurements')
            ->where('measurements.tag_id', $tag);

        if ($from) {
            $query->whereDate('measurements.created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('measurements.created_at', '<=', $to);
        }

        return (float) $query
            ->selectRaw("COALESCE(SUM(measurements.gross_at_jetty - measurements.tare_at_jetty), 0) as total_netto")
            ->value('total_netto');
    }
}
