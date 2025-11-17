<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class MeasurementStatsYear extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';


    protected static string $view = 'filament.widgets.measurement-stats-year';

    public $period; // ini untuk tahun yang dipilih, contoh '2025' atau '2024'

    public function mount(): void
    {
        $this->period = (string) Carbon::now()->year; // Default tahun ini
        $this->form->fill(['period' => $this->period]);
    }

    protected function getViewData(): array
    {
        $netWeight = 0;

        $selectedYear = (int) $this->period;

        // Ambil tanggal 31 Desember tahun sebelumnya jam 16:00:00
        $startDate = Carbon::create($selectedYear - 1, 12, 31, 16, 0, 0);

        // Ambil tanggal 31 Desember tahun ini jam 15:59:59
        $endDate = Carbon::create($selectedYear, 12, 31, 15, 59, 59);

        // Hitung total net weight dari gross - tare
        $netWeight = DB::table('measurements')
            ->whereBetween('jetty_entry_time', [$startDate, $endDate])
            ->selectRaw('SUM(gross_at_jetty - tare_at_jetty) as net')
            ->value('net') ?? 0;

        return [
            'netWeight' => $netWeight,
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Select::make('period')
                ->hiddenLabel()
                ->options([
                    '2025' => '2025',
                    '2024' => '2024',
                ])
                ->default((string) Carbon::now()->year) // default ke tahun sekarang
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->period = $state;
                })
        ];
    }
}
