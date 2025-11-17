<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms; // Import the trait
use Filament\Forms\Contracts\HasForms; // Import the contract

class MeasurementStatsForMine extends Widget implements HasForms // Implement the contract
{
    use InteractsWithForms; // Use the trait

    public static function canView(): bool
    {
        return false;
    }
    protected static string $view = 'filament.widgets.measurement-stats-for-mine';

    public $period = 'today'; // Sesuaikan dengan default pilihan

    public function mount(): void // Add the mount() method
    {
        $this->form->fill();
    }

    protected function getViewData(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $currentMonth = $today->format('Y-m');

        $netWeight = 0;

        if ($this->period === 'today') {
            $netWeight = DB::table('measurements')
                ->whereDate('jetty_entry_time', $today)
                ->selectRaw('SUM(gross_at_mine - tare_at_mine) as net')
                ->value('net') ?? 0;
        } elseif ($this->period === 'yesterday') {
            $netWeight = DB::table('measurements')
                ->whereDate('jetty_entry_time', $yesterday)
                ->selectRaw('SUM(gross_at_mine - tare_at_mine) as net')
                ->value('net') ?? 0;
        } elseif ($this->period === 'this_month') {
            $netWeight = DB::table('measurements')
                ->whereRaw("TO_CHAR(jetty_entry_time, 'YYYY-MM') = ?", [$currentMonth])
                ->selectRaw('SUM(gross_at_mine - tare_at_mine) as net')
                ->value('net') ?? 0;
        } elseif ($this->period === 'year_2025') {
            $netWeight = DB::table('measurements')
                ->whereYear('jetty_entry_time', '2025')
                ->selectRaw('SUM(gross_at_mine - tare_at_mine) as net')
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
                    'today' => 'Hari Ini',
                    'yesterday' => 'Kemarin',
                    'this_month' => 'Bulan Ini',
                    'year_2025' => 'Tahun Ini',
                ])
                ->default('today')
                ->live()
        ];
    }
}
