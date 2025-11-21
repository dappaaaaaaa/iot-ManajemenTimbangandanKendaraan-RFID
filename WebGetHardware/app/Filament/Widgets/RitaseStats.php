<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class RitaseStats extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?int $sort = 4;
    protected static string $view = 'filament.widgets.ritase-stats';

    public $ritaseStartDate;
    public $ritaseEndDate;

    protected function getViewData(): array
    {
        try {
            $Start = $this->ritaseStartDate
                ? Carbon::parse($this->ritaseStartDate)->startOfDay()
                : Carbon::today();
            $End = $this->ritaseEndDate
                ? Carbon::parse($this->ritaseEndDate)->endOfDay()
                : Carbon::tomorrow()->startOfDay();
        } catch (\Exception $e) {
            $Start = Carbon::today();
            $End = Carbon::tomorrow()->startOfDay();
        }
        $ritase = DB::table('measurements')
            ->whereBetween('created_at', [$Start, $End])
            ->count();

        return [
            'ritase' => $ritase,
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Select::make('period')
                ->hidden()
                ->default('today')
        ];
    }
}
