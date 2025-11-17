<?php

namespace App\Filament\Resources\MeasurementResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NetAtJettyChart extends ChartWidget
{
    protected static ?string $heading = 'Total Berat Bersih di Jetty (Kg)';
    protected static ?int $sort = 1;
    public ?string $filter = 'month';

    protected static ?array $options = [
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ];

    protected function getFilters(): ?array
    {
        return [
            'month' => 'This month',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $now = now();

        $backgroundColors = [
            'rgba(255, 99, 132, 0.2)',
            'rgba(255, 159, 64, 0.2)',
            'rgba(255, 205, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(201, 203, 207, 0.2)',
        ];
        $borderColors = [
            'rgb(255, 99, 132)',
            'rgb(255, 159, 64)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)',
            'rgb(54, 162, 235)',
            'rgb(153, 102, 255)',
            'rgb(201, 203, 207)',
        ];

        if ($activeFilter === 'year') {
            $yearNow = $now->year;

            $monthlyNetAtJettyData = [];
            $labels = [];

            $months = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];

            for ($month = 1; $month <= 12; $month++) {
                // Start date: tanggal terakhir bulan sebelumnya jam 16:00:00
                $startDate = Carbon::create(
                    $month === 1 ? $yearNow - 1 : $yearNow,
                    $month === 1 ? 12 : $month - 1,
                    1
                )->endOfMonth()->setTime(16, 0, 0);

                // End date: tanggal terakhir bulan ini jam 15:59:59
                $endDate = Carbon::create($yearNow, $month, 1)->endOfMonth()->setTime(15, 59, 59);

                // Query total net berat di rentang waktu ini
                $netSum = DB::table('measurements')
                    ->where('measurement_status', 'completed')
                    ->whereBetween('jetty_entry_time', [$startDate, $endDate])
                    ->sum(DB::raw('gross_at_jetty - tare_at_jetty'));

                $monthlyNetAtJettyData[] = $netSum;

                // Label bulan dalam bahasa Indonesia
                $labels[] = $months[$month];
            }

            return [
                'datasets' => [
                    [
                        'data' => $monthlyNetAtJettyData,
                        'backgroundColor' => array_pad($backgroundColors, 12, $backgroundColors[0]),
                        'borderColor' => array_pad($borderColors, 12, $borderColors[0]),
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        if ($activeFilter === 'month') {
            $now = now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            // Ambil hari terakhir bulan sebelumnya jam 16:00:00
            $firstWeekStart = $startOfMonth->copy()->subDay()->setTime(16, 0, 0);

            $weeklyNetAtJettyData = [];
            $labels = [];

            $weekNumber = 1;
            $currentStart = $firstWeekStart;

            while ($currentStart->lt($endOfMonth)) {
                $currentEnd = $currentStart->copy()->addDays(7)->subSecond()->setTime(15, 59, 59);

                // Batas akhir tidak boleh lewat dari akhir bulan
                $boundedEnd = $currentEnd->lt($endOfMonth) ? $currentEnd : $endOfMonth->copy()->endOfDay();

                // Query berat bersih
                $netSum = DB::table('measurements')
                    ->where('measurement_status', 'completed')
                    ->whereBetween('jetty_entry_time', [$currentStart, $boundedEnd])
                    ->sum(DB::raw('gross_at_jetty - tare_at_jetty'));

                $weeklyNetAtJettyData[] = $netSum;
                $labels[] = 'Minggu ' . $weekNumber . ' (' . $currentStart->format('d M') . ' - ' . $boundedEnd->format('d M') . ')';

                // Iterasi ke minggu berikutnya
                $currentStart = $currentStart->copy()->addDays(7);
                $weekNumber++;
            }

            return [
                'datasets' => [
                    [
                        'data' => $weeklyNetAtJettyData,
                        'backgroundColor' => array_pad($backgroundColors, count($weeklyNetAtJettyData), $backgroundColors[0]),
                        'borderColor' => array_pad($borderColors, count($weeklyNetAtJettyData), $borderColors[0]),
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        // Filter today (default) tetap seperti sebelumnya
        if ($activeFilter === 'today') {
            $hourlyNetAtJettyRecord = DB::table('measurements')
                ->select(
                    DB::raw('SUM(gross_at_jetty - tare_at_jetty) as net_at_jetty'),
                    DB::raw('EXTRACT(HOUR FROM jetty_entry_time) as hour_number')
                )
                ->where('measurement_status', 'completed')
                ->whereDate('jetty_entry_time', Carbon::now()->toDateString())
                ->groupBy('hour_number')
                ->orderBy('hour_number')
                ->limit(24)
                ->get();

            $hourlyNetAtJettyData = array_fill(0, 24, 0);

            foreach ($hourlyNetAtJettyRecord as $record) {
                $hourlyNetAtJettyData[(int)$record->hour_number] = $record->net_at_jetty ?? 0;
            }

            $hourNames = [];
            for ($i = 0; $i < 24; $i++) {
                $hourNames[] = sprintf('%02d:00', $i);
            }

            return [
                'datasets' => [
                    [
                        'data' => array_values($hourlyNetAtJettyData),
                        'backgroundColor' => array_pad($backgroundColors, 24, $backgroundColors[0]),
                        'borderColor' => array_pad($borderColors, 24, $borderColors[0]),
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $hourNames,
            ];
        }

        return [];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
