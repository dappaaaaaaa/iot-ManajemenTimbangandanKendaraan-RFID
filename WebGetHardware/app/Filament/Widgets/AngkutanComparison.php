<?php

namespace App\Filament\Widgets;

use App\Models\Measurement;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AngkutanComparison extends ChartWidget
{
    protected static ?string $heading = 'Grafik Total Angkutan per Supir';

    protected function getData(): array
    {
        // Query total angkutan per supir
        $data = Measurement::select(
            'rfids.tag_id',
            'rfids.owner_name',
            DB::raw('SUM(COALESCE(measurements.gross_at_jetty - measurements.tare_at_jetty, 0)) as total_angkutan')
        )
            ->join('rfids', 'measurements.tag_id', '=', 'rfids.tag_id')
            ->groupBy('rfids.tag_id', 'rfids.owner_name') // groupBy keduanya
            ->orderByDesc('total_angkutan')
            ->get();

        $labels = $data->map(function ($item) {
            return $item->tag_id . ' (' . $item->owner_name . ')';
        })->toArray();
        $totals = $data->pluck('total_angkutan')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Angkutan (Kg)',
                    'data' => $totals,
                    'backgroundColor' => '#dda15e', // warna bar
                    'borderColor' => '#7f5539',    // opsional: warna border
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Bisa diganti 'line' atau 'pie'
    }
}
