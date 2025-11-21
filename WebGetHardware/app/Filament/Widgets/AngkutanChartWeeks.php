<?php

namespace App\Filament\Widgets;

use App\Models\Measurement;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AngkutanChartWeeks extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Ritase 7 Hari Terakhir';

    protected function getData(): array
    {
        // Ambil data 7 hari terakhir
        $ritase = Measurement::select(
            DB::raw('DATE(created_at) as tanggal'),
            DB::raw('COUNT(*) as total')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Buat array labels dan data
        $labels = [];
        $data = [];

        $periode = collect(range(0, 6))->map(function ($i) {
            return Carbon::now()->subDays(6 - $i)->toDateString();
        });

        foreach ($periode as $tanggal) {
            $labels[] = Carbon::parse($tanggal)->translatedFormat('D');
            $data[] = $ritase->firstWhere('tanggal', $tanggal)->total ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Ritase',
                    'data' => $data,
                    'borderColor' => '#dda15e',
                    'tension' => 0.5,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // bisa diganti 'bar' kalau mau batang
    }
}
