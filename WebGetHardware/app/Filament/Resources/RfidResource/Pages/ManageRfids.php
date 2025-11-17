<?php

namespace App\Filament\Resources\RfidResource\Pages;

use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\RfidResource;
use App\Models\PendingRfidTag;
use Filament\Actions;

class ManageRfids extends ManageRecords
{
    protected static string $resource = RfidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function ($record, array $data) {
                    PendingRfidTag::where('tag_id', $data['tag_id'])->delete();
                }),
        ];
    }
}
