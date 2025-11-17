<?php

namespace App\Filament\Resources\MeasurementResource\Infolists;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class MeasurementHistoryList
{
    public static function make($record): array
    {
        return [
            RepeatableEntry::make('histories')
                ->label('Riwayat Perubahan')
                ->schema([
                    TextEntry::make('user.name')->label('Oleh'),
                    TextEntry::make('action')->label('Aksi'),
                    TextEntry::make('description')->label('Deskripsi'),
                    TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                ])
                ->visible(fn($record) => $record->histories->count() > 0),
        ];
    }
}
