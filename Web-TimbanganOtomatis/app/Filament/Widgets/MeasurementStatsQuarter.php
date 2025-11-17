<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class MeasurementStatsQuarter extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 4;
    protected static string $view = 'filament.widgets.measurement-stats-quarter';

    public $period;
    public $quarter;

    public function mount(): void
    {
        $this->quarter = $this->getQuarter(Carbon::now());
        $this->period = 'Quarter_' . $this->quarter;
        $this->form->fill(['period' => $this->period]);
    }

    protected function getViewData(): array
    {
        $netWeight = 0;
        $year = Carbon::now()->year;
        $startDate = null;
        $endDate = null;

        // Tentukan kuartal saat ini (berdasarkan $this->period)
        $quarter = (int) str_replace('Quarter_', '', $this->period);

        // Tentukan awal dan akhir berdasarkan kuartal saat ini
        if ($quarter === 1) {
            // TW-1: Tidak ada kuartal sebelumnya di tahun yang sama, ambil dari akhir tahun lalu
            $startDate = Carbon::create($year - 1, 12, 31)->setHour(16)->setMinute(0)->setSecond(0);
            $endDate = Carbon::create($year, 3, 31)->setHour(15)->setMinute(59)->setSecond(59);
        } elseif ($quarter === 2) {
            $startDate = Carbon::create($year, 3, 31)->setHour(16)->setMinute(0)->setSecond(0);
            $endDate = Carbon::create($year, 6, 30)->setHour(15)->setMinute(59)->setSecond(59);
        } elseif ($quarter === 3) {
            $startDate = Carbon::create($year, 6, 30)->setHour(16)->setMinute(0)->setSecond(0);
            $endDate = Carbon::create($year, 9, 30)->setHour(15)->setMinute(59)->setSecond(59);
        } elseif ($quarter === 4) {
            $startDate = Carbon::create($year, 9, 30)->setHour(16)->setMinute(0)->setSecond(0);
            $endDate = Carbon::create($year, 12, 31)->setHour(15)->setMinute(59)->setSecond(59);
        }

        if ($startDate && $endDate) {
            $netWeight = DB::table('measurements')
                ->whereBetween('jetty_entry_time', [$startDate, $endDate])
                ->selectRaw('SUM(gross_at_jetty - tare_at_jetty) as net')
                ->value('net') ?? 0;
        }

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
                    'Quarter_1' => 'TW-1 (Jan - Mar)',
                    'Quarter_2' => 'TW-2 (Apr - Jun)',
                    'Quarter_3' => 'TW-3 (Jul - Sep)',
                    'Quarter_4' => 'TW-4 (Oct - Dec)',
                ])
                ->default('Quarter_' . $this->getQuarter(Carbon::now()))
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->period = $state;
                }),
        ];
    }

    private function getQuarter(Carbon $date): int
    {
        return (int) ceil($date->month / 3);
    }
}
