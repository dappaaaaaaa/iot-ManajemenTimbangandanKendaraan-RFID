<?php

namespace App\Filament\Resources\MeasurementResource\Pages;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\MeasurementResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ViewAction;
use App\Filament\Resources\MeasurementResource\Widgets\NetAtJettyChart;
use App\Filament\Resources\MeasurementResource\Widgets\AverageAtJettyChart;

class ListMeasurements extends ListRecords
{
    protected static string $resource = MeasurementResource::class;
    protected static ?string $title = 'Data Timbangan';


    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make()
    //             ->label('Tambah Data Timbangan Baru')
    //             ->icon('heroicon-o-document-plus'),
    //     ];
    // }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            MeasurementResource::getUrl('index') => 'Timbangan',
            'Data Timbangan',
        ];
    }
}
