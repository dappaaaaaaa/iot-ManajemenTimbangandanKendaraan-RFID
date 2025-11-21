<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DetailResource\Pages;
use App\Models\Measurement;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DetailResource extends Resource
{
    protected static ?string $model = Measurement::class;

    protected static ?string $navigationLabel = 'Data Angkutan Truk';
    protected static ?string $navigationGroup = 'Data Angkutan per Truk';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 3;

    public static function getPluralLabel(): string
    {
        return 'Data Angkutan Truk';
    }

    public static function table(Table $table): Table
    {
        // Ambil filter tanggal dari request Filament
        $from  = request('tableFilters.created_at.from');
        $until = request('tableFilters.created_at.until');

        // Query untuk hitung total berat sesuai filter
        $query = Measurement::query();

        if (!$from && !$until) {
            // Default -> hari ini
            $query->whereDate('measurements.created_at', now()->toDateString());
        } else {
            // Jika ada filter, pakai range tanggal
            $query
                ->when($from, fn($q) => $q->where('measurements.created_at', '>=', Carbon::parse($from)->startOfDay()))
                ->when($until, fn($q) => $q->where('measurements.created_at', '<=', Carbon::parse($until)->endOfDay()));
        }

        $totalBeratBersih = $query
            ->selectRaw('ROUND(SUM(measurements.gross_at_jetty - measurements.tare_at_jetty), 2) as total')
            ->value('total') ?? 0;

        return $table
            ->poll('5s')
            ->query(function () {
                return Measurement::query()
                    ->select(
                        DB::raw('MIN(measurements.id) as id'),
                        'measurements.tag_id',
                        DB::raw("COALESCE(rfids.vehicle_number, 'Tidak Terdaftar') as vehicle_number"),
                        DB::raw("COALESCE(rfids.owner_name, 'Tidak Terdaftar') as owner_name"),
                        DB::raw('COUNT(*) as total_ritase'),
                        DB::raw('ROUND(SUM(measurements.gross_at_jetty - measurements.tare_at_jetty), 2) as total_berat')
                    )
                    ->leftJoin('rfids', 'rfids.tag_id', '=', 'measurements.tag_id')
                    ->groupBy('measurements.tag_id', 'rfids.vehicle_number', 'rfids.owner_name');
            })
            ->columns([
                Tables\Columns\TextColumn::make('tag_id')->label('Kartu RFID'),
                Tables\Columns\TextColumn::make('vehicle_number')->label('Nomor Kendaraan'),
                Tables\Columns\TextColumn::make('owner_name')->label('Nama Supir'),
                Tables\Columns\TextColumn::make('total_ritase')->label('Jumlah Ritase'),
                Tables\Columns\TextColumn::make('total_berat')
                    ->label('Total Berat Bersih (Kg)')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, ',', '.')),
            ])
            ->filters([
                SelectFilter::make('tag_id')
                    ->label('Filter Kartu RFID')
                    ->options(
                        \App\Models\Rfid::query()
                            ->pluck('tag_id', 'tag_id')
                            ->toArray()
                    )
                    ->query(fn($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn($q, $value) => $q->where('measurements.tag_id', $value)
                    )),

                SelectFilter::make('vehicle_number')
                    ->label('Filter Nomor Kendaraan')
                    ->options(
                        \App\Models\Rfid::query()
                            ->pluck('vehicle_number', 'vehicle_number')
                            ->toArray()
                    )
                    ->query(fn($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn($q, $value) => $q->where('rfids.vehicle_number', $value)
                    )),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if (!$from && !$until) {
                            return $query->whereDate('measurements.created_at', now()->toDateString());
                        }

                        return $query
                            ->when($from, fn($q) => $q->where('measurements.created_at', '>=', Carbon::parse($from)->startOfDay()))
                            ->when($until, fn($q) => $q->where('measurements.created_at', '<=', Carbon::parse($until)->endOfDay()));
                    }),
            ])
            ->defaultSort('total_ritase', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn($record) => route('filament.admin.resources.details.view', $record->tag_id))
                    ->button()
                    ->color('primary'),
            ])
            ->heading('Data Angkutan Truk')
            ->description(
                fn() =>
                'Total Berat Bersih: ' . number_format(
                    (float) Measurement::query()
                        ->when(
                            !request('tableFilters.created_at.from') && !request('tableFilters.created_at.until'),
                            fn($q) => $q->whereDate('measurements.created_at', now()->toDateString())
                        )
                        ->when(
                            request('tableFilters.created_at.from'),
                            fn($q, $from) => $q->where('measurements.created_at', '>=', Carbon::parse($from)->startOfDay())
                        )
                        ->when(
                            request('tableFilters.created_at.until'),
                            fn($q, $until) => $q->where('measurements.created_at', '<=', Carbon::parse($until)->endOfDay())
                        )
                        ->selectRaw('ROUND(SUM(measurements.gross_at_jetty - measurements.tare_at_jetty), 2) as total')
                        ->value('total') ?? 0,
                    2,
                    ',',
                    '.'
                ) . ' Kg'
            )
            ->emptyStateHeading('Belum ada data angkutan truk')
            ->emptyStateDescription('Data angkutan truk akan muncul setelah proses penimbangan hari ini dilakukan.')
            ->headerActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDetails::route('/'),
            'view' => Pages\ViewDetail::route('/{record}'),
        ];
    }
}
