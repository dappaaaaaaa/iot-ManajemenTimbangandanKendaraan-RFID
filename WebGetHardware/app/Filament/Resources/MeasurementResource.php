<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Measurement;
use Maatwebsite\Excel\Excel;
use Filament\Resources\Resource;
use App\Exports\MeasurementsExport;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MeasurementResource\Pages;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;


class MeasurementResource extends Resource
{
    protected static ?string $model = Measurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Timbangan';

    protected static ?int $navigationSort = 2;


    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\MeasurementResource\Widgets\NetAtJettyChart::class,
        ];
    }

    public static function getPluralLabel(): string
    {
        return 'Timbangan';
    }

    // Hook untuk set default approved status
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure default approved status
        $data['is_pending'] = $data['is_pending'] ?? false;
        $data['is_approved'] = $data['is_approved'] ?? true;

        return $data;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make("vehicle_number")->label("Nomor Kendaraan"),

                Forms\Components\Fieldset::make('Mine')
                    ->schema([
                        Forms\Components\TextInput::make("gross_at_mine")->label("Berat Kotor")->numeric()->inputMode('decimal'),
                        Forms\Components\TextInput::make("tare_at_mine")->label("Berat Tara")->numeric()->inputMode('decimal'),
                    ]),

                Forms\Components\Fieldset::make('Jetty')
                    ->schema([
                        Forms\Components\TextInput::make("gross_at_jetty")->label("Berat Kotor")->numeric()->inputMode('decimal'),
                        Forms\Components\TextInput::make("tare_at_jetty")->label("Berat Tara")->numeric()->inputMode('decimal'),
                        Forms\Components\DateTimePicker::make("jetty_entry_time")->label("Waktu Masuk Jetty"),
                    ]),

                Forms\Components\FileUpload::make('attachments')->image(),

                // Hidden fields untuk set default approved status (optional, karena sudah ada di model)
                Forms\Components\Hidden::make('is_pending')->default(false),
                Forms\Components\Hidden::make('is_approved')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gross_at_mine')
                    ->label(new HtmlString('Berat Kotor Tambang <br/> 1'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('gross_at_jetty')
                    ->label(new HtmlString('Berat Kotor di Jetty <br/> 2'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('loses')
                    ->getStateUsing(function ($record) {
                        $grossAtMine = $record->gross_at_mine ?? 0;
                        $tareAtMine = $record->tare_at_mine ?? 0;
                        $grossAtJetty = $record->gross_at_jetty ?? 0;
                        $tareAtJetty = $record->tare_at_jetty ?? 0;

                        $netAtMine = $grossAtMine - $tareAtMine;
                        $netAtJetty = $grossAtJetty - $tareAtJetty;
                        return $netAtMine - $netAtJetty;
                    })
                    ->label(new HtmlString('Loses <br/> 3 (1 - 2)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('tare_at_jetty')
                    ->label(new HtmlString('Berat Tara <br/> 4'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('net_at_jetty')
                    ->getStateUsing(function ($record) {
                        $grossAtJetty = $record->gross_at_jetty ?? 0;
                        $tareAtJetty = $record->tare_at_jetty ?? 0;
                        return $grossAtJetty - $tareAtJetty;
                    })
                    ->label(new HtmlString('Berat Bersih <br/> 5 (2 - 4)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable()
                    ->label(new HtmlString('Nomor Kendaraan <br/> 6')),

                Tables\Columns\TextColumn::make('jetty_entry_time')
                    ->label(new HtmlString('Waktu Masuk Jetty <br/> 7'))
                    ->searchable()
                    ->dateTime(),
                TextColumn::make('attachments')
                    ->label(new HtmlString('Foto Tiket <br/> 8'))
                    ->formatStateUsing(function ($state, $record) {
                        $imgUrl = asset('storage/' . $state);
                        $downloadUrl = route('measurement.exportAttachmentPdf', $record);

                        return new HtmlString("
            <a href='$downloadUrl'>
                <img src='$imgUrl' alt='foto' style='height: 60px; width: 300px; border-radius: 4px;' />
            </a>
        ");
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(new HtmlString('Ditambahkan Oleh <br/> 9'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('editor.name')
                    ->label(new HtmlString('Terakhir Diedit Oleh <br/> 10')),

                // Kolom status gabungan dengan default approved
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Status Verifikasi')
                    ->getStateUsing(function ($record) {
                        // Handle null values dengan accessor dari model
                        $isPending = $record->is_pending; // Accessor akan handle null
                        $isApproved = $record->is_approved; // Accessor akan handle null

                        if ($isPending === true) {
                            return 'pending';
                        }

                        if ($isPending === false) {
                            return $isApproved === true ? 'approved' : 'rejected';
                        }

                        // Default adalah approved
                        return 'approved';
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            'pending' => 'Menunggu Konfirmasi',
                            default => 'Disetujui',
                        };
                    })
                    ->colors([
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'pending',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                        'heroicon-o-clock' => 'pending',
                    ]),
            ])
            ->defaultSort('jetty_entry_time', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\Filter::make('jetty_entry_time')
                    ->form([
                        Forms\Components\DateTimePicker::make('from')->label('Dari tanggal & waktu'),
                        Forms\Components\DateTimePicker::make('until')->label('Sampai tanggal & waktu')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date) => $query->where('jetty_entry_time', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date) => $query->where('jetty_entry_time', '<=', $date));
                    }),

                // Filter untuk status verifikasi dengan support UUID
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Status Verifikasi')
                    ->options([
                        'pending' => 'Menunggu Konfirmasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }

                        return match ($data['value']) {
                            'pending' => $query->where('is_pending', true),
                            'approved' => $query->where(function ($query) {
                                $query->where(function ($q) {
                                    // Explicitly approved
                                    $q->where('is_pending', false)->where('is_approved', true);
                                })->orWhere(function ($q) {
                                    // Default approved (null values handled by accessor)
                                    $q->whereNull('is_pending')->whereNull('is_approved');
                                });
                            }),
                            'rejected' => $query->where('is_pending', false)->where('is_approved', false),
                            default => $query,
                        };
                    }),

                // Filter untuk histori aktivitas
                Tables\Filters\SelectFilter::make('activity_filter')
                    ->label('Filter Aktivitas')
                    ->options([
                        'new_today' => 'Dibuat Hari Ini',
                        'edited_today' => 'Diedit Hari Ini',
                        'new_week' => 'Dibuat Minggu Ini',
                        'edited_week' => 'Diedit Minggu Ini',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }

                        return match ($data['value']) {
                            'new_today' => $query->whereDate('created_at', today()),
                            'edited_today' => $query
                                ->whereBetween('updated_at', [now()->startOfDay(), now()->endOfDay()])
                                ->whereColumn('updated_at', '!=', 'created_at'),
                            'new_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            'edited_week' => $query->whereBetween('updated_at', [
                                now()->startOfWeek(),
                                now()->endOfWeek()
                            ])
                                ->whereColumn('updated_at', '!=', 'created_at'),
                            default => $query,
                        };
                    }),

            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning') // <- warna oranye
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(\Carbon\Carbon::now()->startOfMonth()->subDay()->setTime(16, 0, 0)),

                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->default(\Carbon\Carbon::now()->endOfMonth()->setTime(15, 59, 59)),
                    ])
                    ->action(function (array $data) {
                        $from = $data['from']
                            ? \Carbon\Carbon::parse($data['from'])->setTime(16, 0, 0)
                            : null;

                        $until = $data['until']
                            ? \Carbon\Carbon::parse($data['until'])->setTime(15, 59, 59)
                            : null;

                        $export = new \App\Exports\MeasurementsExport($from, $until);
                        $fileName = $export->getFileName();

                        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Export Excel')
                    ->modalDescription('Rentang default adalah satu bulan dari hari terakhir jam 16:00 bulan lalu hingga hari terakhir jam 15:59 bulan ini. Ubah jika ingin rentang berbeda.'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(function ($record) {
                        // Tampilkan tombol jika user adalah accessor dan data pending
                        return auth()->user()->hasRole('accessor') &&
                            $record->is_pending === true;
                    })
                    ->action(function ($record) {
                        $pendingChanges = json_decode($record->pending_changes ?? '{}', true);

                        // Update nilai-nilai utama
                        if (!empty($pendingChanges)) {
                            $record->fill($pendingChanges);
                        }

                        $record->is_pending = false;
                        $record->is_approved = true;
                        $record->approved_by = auth()->id();
                        $record->pending_changes = null; // Hapus setelah disetujui
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Perubahan disetujui dan diterapkan.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function ($record) {
                        // Tampilkan tombol jika user adalah accessor dan data belum ditolak
                        return auth()->user()->hasRole('accessor') &&
                            ($record->is_pending === true ||
                                ($record->is_pending === false && $record->is_approved === true));
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Ambil nilai sebelum diedit
                        $changes = json_decode($record->pending_changes, true);

                        if (is_array($changes)) {
                            foreach ($changes as $field => $oldValue) {
                                $record->{$field} = $oldValue;
                            }
                        }

                        $record->pending_changes = null;
                        $record->is_pending = false;
                        $record->is_approved = false;
                        $record->rejected_by = auth()->id();
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Perubahan ditolak & dikembalikan ke data sebelumnya')
                            ->danger()
                            ->send();
                    }),

                Action::make('set_pending')
                    ->label('Set Menunggu')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(function ($record) {
                        // Tampilkan tombol jika user adalah accessor dan data sudah diverifikasi
                        return auth()->user()->hasRole('accessor') &&
                            $record->is_pending === false;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ubah ke Status Menunggu')
                    ->modalDescription('Apakah Anda yakin ingin mengubah status data ini menjadi menunggu konfirmasi?')
                    ->action(function ($record) {
                        // Update tanpa mengubah editor_id (hanya verifikasi)
                        $record->updateQuietly([
                            'is_pending' => true,
                            'is_approved' => null,
                        ]);

                        // Log set pending action
                        $record->logSetPending();

                        \Filament\Notifications\Notification::make()
                            ->title('Status diubah menjadi menunggu konfirmasi')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->isEditableThisMonth())
                    ->tooltip('Data hanya dapat diedit pada bulan yang sama dengan tanggal Jetty Entry'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeasurements::route('/'),
            'create' => Pages\CreateMeasurement::route('/create'),
            'edit' => Pages\EditMeasurement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'editor', 'histories.user']);
    }
}
