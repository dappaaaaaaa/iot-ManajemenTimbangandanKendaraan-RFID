<?php

namespace App\Filament\Resources\MeasurementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\MeasurementResource;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditMeasurement extends EditRecord
{
    protected static string $resource = MeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            // Tombol manual buka gate
            Actions\Action::make('open_gate')
                ->label('Open Gate')
                ->color('success')
                ->icon('heroicon-o-lock-open')
                ->action(function () {
                    try {
                        $this->record->openGate();

                        Notification::make()
                            ->title('Gate berhasil dibuka!')
                            ->success()
                            ->send();

                        Log::info("Gate opened manually", ['id' => $this->record->id]);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal membuka gate')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        Log::error("Gate open error (manual)", [
                            'id' => $this->record->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        try {
            $this->record->openGate();

            Log::info("Gate opened automatically after save", [
                'id' => $this->record->id
            ]);
        } catch (\Exception $e) {
            Log::error("Gate open error (afterSave)", [
                'id' => $this->record->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
