<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected string $tag;
    protected ?string $from;
    protected ?string $to;

    public function __construct(string $tag, ?string $from = null, ?string $to = null)
    {
        $this->tag  = $tag;
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $query = DB::table('measurements')
            ->where('measurements.tag_id', $this->tag)
            ->selectRaw("
                DATE(measurements.created_at) as tanggal,
                TO_CHAR(measurements.created_at, 'HH24:MI') as jam,
                ROUND(measurements.gross_at_jetty - measurements.tare_at_jetty, 2) as netto
            ");

        if ($this->from) {
            $query->whereDate('measurements.created_at', '>=', $this->from);
        }
        if ($this->to) {
            $query->whereDate('measurements.created_at', '<=', $this->to);
        }

        return $query->orderBy('measurements.created_at', 'asc')->get();
    }

    public function map($row): array
    {
        return [
            $row->tanggal,
            $row->jam,
            number_format((float) $row->netto, 2, ',', '.'),
        ];
    }

    public function headings(): array
    {
        $periode = null;

        if ($this->from && $this->to) {
            $periode = 'Periode: ' . \Carbon\Carbon::parse($this->from)->translatedFormat('d F Y') .
                ' - ' . \Carbon\Carbon::parse($this->to)->translatedFormat('d F Y');
        } elseif ($this->from) {
            $periode = 'Mulai ' . \Carbon\Carbon::parse($this->from)->translatedFormat('d F Y');
        } elseif ($this->to) {
            $periode = 'Sampai ' . \Carbon\Carbon::parse($this->to)->translatedFormat('d F Y');
        }

        return array_filter([
            $periode,
            "Tanggal",
            "Jam",
            "Berat Bersih (Kg)",
        ]);
    }
}
