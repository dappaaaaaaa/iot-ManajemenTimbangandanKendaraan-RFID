<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class MeasurementStatsYesterday extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 2;
    protected static string $view = 'filament.widgets.measurement-stats-yesterday';

    public $period = 'yesterday';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getViewData(): array
    {
        // Dari jam 4 sore dua hari lalu sampai jam 4 sore kemarin
        $start = Carbon::yesterday()->subDay()->setHour(16)->setMinute(0)->setSecond(0);
        $end = Carbon::yesterday()->setHour(15)->setMinute(59)->setSecond(59);

        $netWeight = DB::table('measurements')
            ->whereBetween('jetty_entry_time', [$start, $end])
            ->selectRaw('SUM(gross_at_jetty- tare_at_jetty) as net')
            ->value('net') ?? 0;

        return [
            'netWeight' => $netWeight,
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Select::make('period')
                ->hidden()
                ->default('yesterday'),
        ];
    }
}
