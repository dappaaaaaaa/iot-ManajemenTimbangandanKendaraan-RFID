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
                ->label('Tambah RFID')
                ->after(function ($record, array $data) {
                    // Hapus data pending setelah RFID berhasil ditambahkan
                    PendingRfidTag::where('tag_id', $data['tag_id'])->delete();
                }),
        ];
    }
}
