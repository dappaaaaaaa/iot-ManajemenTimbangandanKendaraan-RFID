<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Measurement;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MeasurementsExport implements FromView, ShouldAutoSize
{
    protected $from;
    protected $until;

    public function __construct($from = null, $until = null)
    {
        $this->from = $from ? Carbon::parse($from)->startOfDay() : null;
        $this->until = $until ? Carbon::parse($until)->endOfDay() : null;

        if ($this->from && $this->until) {
            $diffInDays = $this->from->diffInDays($this->until);
            if ($diffInDays > 92) {
                throw ValidationException::withMessages([
                    'date_range' => 'Rentang waktu ekspor maksimal adalah 3 bulan (92 hari).',
                ]);
            }
        }
    }

    public function view(): View
    {
        $query = Measurement::query();

        if ($this->from) {
            $query->where('jetty_entry_time', '>=', $this->from);
        }

        if ($this->until) {
            $query->where('jetty_entry_time', '<=', $this->until);
        }

        $query->orderBy('jetty_entry_time', 'asc');

        return view('exports.measurements', [
            'measurements' => $query->get(),
        ]);
    }

    public function getFileName(): string
    {
        $fromFormatted = $this->from ? $this->from->format('d-m-Y') : 'semua';
        $untilFormatted = $this->until ? $this->until->format('d-m-Y') : 'semua';

        return 'export_timbangan_' . $fromFormatted . '_sampai_' . $untilFormatted . '.xlsx';
    }
}
