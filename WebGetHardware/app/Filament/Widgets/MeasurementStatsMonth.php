<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class MeasurementStatsMonth extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 1;
    protected static string $view = 'filament.widgets.measurement-stats-month';

    public $startDateTime;
    public $endDateTime;

    public function mount(): void
    {
        $this->startDateTime = Carbon::today()->startOfDay();
        $this->endDateTime = Carbon::today()->endOfDay();

        $this->form->fill([
            'startDateTime' => $this->startDateTime,
            'endDateTime' => $this->endDateTime,
        ]);
    }

    public function getFormSchema(): array
    {
        $min = Carbon::now()->subyear()->setTime(0, 0);
        $max = Carbon::now()->endOfMonth()->setTime(0, 0, 0);

        return [
            DateTimePicker::make('startDateTime')
                ->label('Dari Tanggal & Jam')
                ->required()
                ->minDate($min)
                ->maxDate($max),

            DateTimePicker::make('endDateTime')
                ->label('Sampai Tanggal & Jam')
                ->required()
                ->minDate($min)
                ->maxDate($max),
        ];
    }

    public function updateDateRange() {}
    protected function getViewData(): array
    {
        try {
            $start = Carbon::parse($this->startDateTime);
            $end = Carbon::parse($this->endDateTime);
        } catch (\Exception $e) {
            $start = Carbon::now()->subMonthNoOverflow()->endOfMonth()->setTime(0, 0);
            $end = Carbon::now()->endOfMonth()->setTime(0, 0, 0);
        }

        $netWeight = DB::table('measurements')
            ->whereBetween('jetty_entry_time', [$start, $end])
            ->selectRaw('SUM(gross_at_jetty - tare_at_jetty) as net')
            ->value('net') ?? 0;

        return [
            'netWeight' => $netWeight,
        ];
    }
}
