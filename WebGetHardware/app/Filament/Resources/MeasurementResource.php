<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Measurement;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MeasurementResource\Pages;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\TextColumn;

class MeasurementResource extends Resource
{
    protected static ?string $model = Measurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Data Timbangan';

    protected static ?string $navigationGroup = 'Data Timbangan';

    protected static ?int $navigationSort = 2;

    public static function getPluralLabel(): string
    {
        return 'Timbangan';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make("tag_id")->label("Kartu RFID"),
                Forms\Components\TextInput::make("vehicle_number")->label("Nomor Kendaraan"),

                Forms\Components\Fieldset::make('Mine')
                    ->schema([
                        Forms\Components\TextInput::make("gross_at_mine")->label("Berat Kotor")->numeric()->inputMode('decimal'),
                    ]),

                Forms\Components\Fieldset::make('Jetty')
                    ->schema([
                        Forms\Components\TextInput::make("gross_at_jetty")->label("Berat Kotor")->numeric()->inputMode('decimal'),
                        Forms\Components\TextInput::make("tare_at_jetty")->label("Berat Tara")->numeric()->inputMode('decimal'),
                        Forms\Components\DateTimePicker::make("jetty_entry_time")->label("Waktu Masuk Jetty"),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                Tables\Columns\TextColumn::make('gross_at_mine')
                    ->label(new HtmlString('Berat Kotor Tambang <br/> (Kg)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('gross_at_jetty')
                    ->label(new HtmlString('Berat Kotor di Jetty <br/> (Kg)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('loses')
                    ->getStateUsing(function ($record) {
                        $grossAtMine = $record->gross_at_mine ?? 0;
                        $grossAtJetty = $record->gross_at_jetty ?? 0;
                        $tareAtJetty = $record->tare_at_jetty ?? 0;

                        $netAtMine = $grossAtMine - $tareAtJetty;
                        $netAtJetty = $grossAtJetty - $tareAtJetty;
                        return $netAtMine - $netAtJetty;
                    })
                    ->label(new HtmlString('Loses <br/> (Kg)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('tare_at_jetty')
                    ->label(new HtmlString('Berat Tara <br/> (Kg)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('net_at_jetty')
                    ->getStateUsing(function ($record) {
                        $grossAtJetty = $record->gross_at_jetty ?? 0;
                        $tareAtJetty = $record->tare_at_jetty ?? 0;
                        return $grossAtJetty - $tareAtJetty;
                    })
                    ->label(new HtmlString('Berat Bersih <br/> (Kg)'))
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, ',', '.')),

                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable()
                    ->label(new HtmlString('Nomor Kendaraan')),

                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->label(new HtmlString('Nama Supir')),

                Tables\Columns\TextColumn::make('tag_id')
                    ->searchable()
                    ->label(new HtmlString('Kartu RFID')),

                Tables\Columns\TextColumn::make('jetty_entry_time')
                    ->label(new HtmlString('Waktu Masuk Jetty'))
                    ->searchable()
                    ->dateTime(),
            ])
            ->defaultSort('jetty_entry_time', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->headerActions([
                Action::make('measurements')
                    ->label('Tambah Data Timbangan Baru')
                    ->icon('heroicon-o-document-plus')
                    ->url(route('filament.admin.resources.measurements.create')),

                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->action(function (array $data) {
                        $from = $data['from']
                            ? Carbon::parse($data['from'])->startOfDay()
                            : null;
                        $until = $data['until']
                            ? Carbon::parse($data['until'])->endOfDay()
                            : null;

                        $export = new \App\Exports\MeasurementsExport($from, $until);
                        $fileName = $export->getFileName();

                        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit data timbangan'),
                Tables\Actions\DeleteAction::make(),
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
        return parent::getEloquentQuery()->with(['user']);
    }
}
