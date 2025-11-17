<?php

namespace App\Filament\Resources\RfidResource\Pages;

use App\Filament\Resources\RfidResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRfid extends EditRecord
{
    protected static string $resource = RfidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
