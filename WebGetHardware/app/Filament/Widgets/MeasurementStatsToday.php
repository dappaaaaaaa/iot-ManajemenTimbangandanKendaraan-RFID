<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms; // Import the trait
use Filament\Forms\Contracts\HasForms; // Import the contract

class MeasurementStatsToday extends Widget implements HasForms // Implement the contract
{
    use InteractsWithForms; // Use the trait

    protected static ?int $sort = 1;
    protected static string $view = 'filament.widgets.measurement-stats-today';

    public $period = 'today'; // Set default period as today

    public function mount(): void // Add the mount() method
    {
        $this->form->fill();
    }

    protected function getViewData(): array
    {
        // Ambil waktu dari jam 16:00 kemarin sampai jam 16:00 hari ini
        $start = Carbon::yesterday()->setHour(16)->setMinute(0)->setSecond(0);
        $end = Carbon::today()->setHour(15)->setMinute(59)->setSecond(59);

        // Ambil total net weight (gross - tare) berdasarkan rentang waktu tersebut
        $netWeight = DB::table('measurements')
            ->whereBetween('jetty_entry_time', [$start, $end])
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
                ->hidden() // Hide the period select dropdown since we are only showing "Today"
                ->default('today') // Set default as today, and make it hidden
        ];
    }
}
