<?php

namespace App\Filament\Resources\MeasurementResource\Pages;

use App\Models\MeasurementHistory;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Filament\Resources\MeasurementResource;
use App\Imports\MeasurementsImport;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use App\Filament\Resources\MeasurementResource\Widgets\NetAtJettyChart;
use App\Filament\Resources\MeasurementResource\Widgets\AverageAtJettyChart;

class ListMeasurements extends ListRecords
{
    protected static string $resource = MeasurementResource::class;
    protected static ?string $title = 'Data Timbangan';


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Timbangan Baru')
                ->icon('heroicon-o-document-plus'),

            Actions\Action::make('import_measurements')
                ->label('Import Data Timbangan')
                ->icon('heroicon-o-document-arrow-down')
                ->form([
                    Forms\Components\FileUpload::make('attachment')
                        ->label('Pilih File Excel (.xlsx)')
                        ->disk('public')
                        ->directory('imports')
                        ->required()
                        ->acceptedFileTypes(['.xls', '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('public')->path($data['attachment']);

                    try {
                        Excel::import(new MeasurementsImport(Auth::id()), $filePath);

                        Notification::make()
                            ->title('✅ Data berhasil diimpor')
                            ->success()
                            ->send();
                    } catch (\Throwable $th) {
                        Log::error('❌ Gagal mengimpor file: ' . $th->getMessage());

                        Notification::make()
                            ->title('❌ Import gagal')
                            ->body($th->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        if (Storage::disk('public')->exists($data['attachment'])) {
                            Storage::disk('public')->delete($data['attachment']);
                        }
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                Action::make('approveMeasurement')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => auth()->user()->hasRole('accessor') && $record->is_pending)
                    ->action(function ($record) {
                        // Pastikan pending_changes didekode dari JSON string ke array
                        $changes = is_string($record->pending_changes)
                            ? json_decode($record->pending_changes, true)
                            : ($record->pending_changes ?? []);

                        Log::info('🔍 Pending changes sebelum apply', $changes);

                        if (empty($changes)) {
                            Notification::make()
                                ->title('Tidak ada perubahan yang diajukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Paksa isi menggunakan forceFill agar Laravel deteksi perubahan
                        $record->forceFill($changes);

                        $record->is_pending = false;
                        $record->is_approved = true;
                        $record->approved_by = auth()->id();
                        $record->pending_changes = null;

                        // $record->bypassObserver = true;

                        Log::info('📋 Perubahan yang akan disimpan:', $record->getDirty());

                        $record->save();

                        Log::info('✅ Setelah approve', [
                            'vehicle_number' => $record->vehicle_number,
                            'pending_changes' => $record->pending_changes,
                        ]);

                        Notification::make()
                            ->title('Perubahan disetujui dan diterapkan.')
                            ->success()
                            ->send();
                    }),


                Action::make('rejectMeasurement')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn($record) => $record->is_pending && auth()->user()->hasRole('accessor'))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'is_pending' => false,
                            'is_approved' => false,
                            'rejected_by' => auth()->id(),
                            'pending_changes' => null,
                        ]);

                        MeasurementHistory::create([
                            'measurement_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'rejected',
                            'description' => 'Perubahan ditolak oleh accessor',
                        ]);

                        Notification::make()
                            ->title('Perubahan ditolak')
                            ->danger()
                            ->send();
                    }),

                ViewAction::make('viewHistory')
                    ->label('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->modalHeading('Riwayat Perubahan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(
                        fn($record) =>
                        View::make('filament.resources.measurements.history-modal', [
                            'record' => $record,
                        ])
                    ),
            ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            // '/' => 'Dashboard', // Contoh: menambahkan link ke Dashboard
            MeasurementResource::getUrl('index') => 'Timbangan', // Mengubah nama resource
            'Data Timbangan', // Mengubah label halaman saat ini
        ];
    }
}
