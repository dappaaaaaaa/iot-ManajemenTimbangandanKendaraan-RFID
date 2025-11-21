<?php

namespace App\Filament\Resources\MeasurementResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\MeasurementResource;
use App\Helpers\GateHelper;
use Illuminate\Support\Facades\Log;

class CreateMeasurement extends CreateRecord
{
    protected static string $resource = MeasurementResource::class;

    // protected function afterCreate(): void
    // {
    //     $this->record->openGate();
    // }
}
