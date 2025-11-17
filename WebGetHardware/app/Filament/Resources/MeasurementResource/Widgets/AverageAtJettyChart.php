<?php

namespace App\Filament\Resources\MeasurementResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AverageAtJettyChart extends ChartWidget
{
    protected static ?string $heading = 'Rata-Rata Berat Bersih di Jetty (Kgs)';
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
            $currentYear = $now->year;
            $monthlyAvgAtJettyData = [];

            $monthNames = [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ];

            for ($month = 1; $month <= 12; $month++) {
                // Start date: tanggal terakhir bulan sebelumnya jam 16:00:00
                $startDate = Carbon::create(
                    $month === 1 ? $currentYear - 1 : $currentYear,
                    $month === 1 ? 12 : $month - 1,
                    1
                )->endOfMonth()->setTime(16, 0, 0);

                // End date: tanggal terakhir bulan ini jam 15:59:59
                $endDate = Carbon::create($currentYear, $month, 1)->endOfMonth()->setTime(15, 59, 59);

                // Query rata-rata net berat di rentang waktu ini
                $avgNet = DB::table('measurements')
                    ->where('measurement_status', 'completed')
                    ->whereBetween('jetty_entry_time', [$startDate, $endDate])
                    ->avg(DB::raw('gross_at_jetty - tare_at_jetty'));

                $monthlyAvgAtJettyData[] = round($avgNet ?? 0, 2);
            }

            return [
                'datasets' => [
                    [
                        'data' => array_values($monthlyAvgAtJettyData),
                        'backgroundColor' => array_pad($backgroundColors, 12, $backgroundColors[0]),
                        'borderColor' => array_pad($borderColors, 12, $borderColors[0]),
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $monthNames,
            ];
        }

        if ($activeFilter === 'month') {
            $now = now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            // Ambil hari terakhir bulan sebelumnya jam 16:00:00 (sama seperti NetAtJettyChart)
            $firstWeekStart = $startOfMonth->copy()->subDay()->setTime(16, 0, 0);

            $weeklyAvgAtJettyData = [];
            $labels = [];

            $weekNumber = 1;
            $currentStart = $firstWeekStart;

            while ($currentStart->lt($endOfMonth)) {
                $currentEnd = $currentStart->copy()->addDays(7)->subSecond()->setTime(15, 59, 59);

                // Batas akhir tidak boleh lewat dari akhir bulan
                $boundedEnd = $currentEnd->lt($endOfMonth) ? $currentEnd : $endOfMonth->copy()->endOfDay();

                // Query rata-rata net berat di rentang waktu ini
                $avgNet = DB::table('measurements')
                    ->where('measurement_status', 'completed')
                    ->whereBetween('jetty_entry_time', [$currentStart, $boundedEnd])
                    ->avg(DB::raw('gross_at_jetty - tare_at_jetty'));

                $weeklyAvgAtJettyData[] = round($avgNet ?? 0, 2);
                $labels[] = 'Minggu ' . $weekNumber . ' (' . $currentStart->format('d M') . ' - ' . $boundedEnd->format('d M') . ')';

                // Iterasi ke minggu berikutnya
                $currentStart = $currentStart->copy()->addDays(7);
                $weekNumber++;
            }

            return [
                'datasets' => [
                    [
                        'data' => $weeklyAvgAtJettyData,
                        'backgroundColor' => array_pad($backgroundColors, count($weeklyAvgAtJettyData), $backgroundColors[0]),
                        'borderColor' => array_pad($borderColors, count($weeklyAvgAtJettyData), $borderColors[0]),
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        // Filter today
        if ($activeFilter === 'today') {
            $avgToday = DB::table('measurements')
                ->where('measurement_status', 'completed')
                ->whereDate('jetty_entry_time', Carbon::today())
                ->avg(DB::raw('gross_at_jetty - tare_at_jetty'));

            return [
                'datasets' => [
                    [
                        'data' => [round($avgToday ?? 0, 2)],
                        'backgroundColor' => [$backgroundColors[0]],
                        'borderColor' => [$borderColors[0]],
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => ['Hari Ini'],
            ];
        }

        return [];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
