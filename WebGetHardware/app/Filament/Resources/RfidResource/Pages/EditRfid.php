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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        if (isset($data['gross_at_mine']) && $data['gross_at_mine'] != $record->gross_at_mine) {
            $data['is_manual_gross_mine'] = true;
        }

        if (isset($data['tare_at_mine']) && $data['tare_at_mine'] != $record->tare_at_mine) {
            $data['is_manual_tare_mine'] = true;
        }

        if (isset($data['gross_at_jetty']) && $data['gross_at_jetty'] != $record->gross_at_jetty) {
            $data['is_manual_gross_jetty'] = true;
        }

        if (isset($data['tare_at_jetty']) && $data['tare_at_jetty'] != $record->tare_at_jetty) {
            $data['is_manual_tare_jetty'] = true;
        }

        return parent::mutateFormDataBeforeSave($data);
    }
}
