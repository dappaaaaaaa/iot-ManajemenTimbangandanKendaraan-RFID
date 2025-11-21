<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class RfidTagStats extends Widget implements HasForms
{
    use InteractsWithForms;


    protected static ?int $sort = 5;
    protected static string $view = 'filament.widgets.rfid-stats';

    protected function getViewData(): array
    {
        $rfid = DB::table('rfids')->count();

        return [
            'rfid' => $rfid,
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
