<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Rfid;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use App\Filament\Resources\RfidResource\Pages;

class RfidResource extends Resource
{
    protected static ?string $model = Rfid::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Data RFID';
    protected static ?string $navigationGroup = 'RFID';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tag_id')
                    ->label('Tag ID')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled()   // Agar user tidak edit
                    ->dehydrated(true) // Penting agar tetap terkirim ke database
                    ->readOnly(false) // Tidak perlu readonly karena sudah disabled
                    ->maxLength(255),

                Forms\Components\TextInput::make('owner_name')
                    ->label('Nama Supir')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('vehicle_number')
                    ->label('Nomor Kendaraan')
                    ->required()
                    ->maxLength(250),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tag_id')
                    ->label('Tag ID'),
                Tables\Columns\TextColumn::make('owner_name')
                    ->label('Nama Supir')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->label('Nomor Kendaraan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('scanned_at')
                    ->label('Scanned At')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRfids::route('/'),
            'create' => Pages\CreateRfid::route('/create'),
            'edit' => Pages\EditRfid::route('/{record}/edit'),
        ];
    }
}
