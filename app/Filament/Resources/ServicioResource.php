<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicioResource\Pages;
use App\Filament\Resources\ServicioResource\RelationManagers;
use App\Models\Servicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServicioResource extends Resource
{
    protected static ?string $model = Servicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // En app/Filament/Resources/ServicioResource.php

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->label('Nombre del Servicio'),
                
            Forms\Components\TextInput::make('precio_costo')
                ->required()
                ->numeric()
                ->prefix('Q') // Símbolo de Quetzales
                ->label('Costo (Lo que pagas tú)'),

            Forms\Components\TextInput::make('precio_venta_sugerido')
                ->required()
                ->numeric()
                ->prefix('Q')
                ->label('Precio Venta (Público)'),

            Forms\Components\TextInput::make('max_perfiles')
                ->required()
                ->numeric()
                ->default(1)
                ->label('Máximo de Perfiles'),
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('nombre')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('precio_costo')
                ->money('GTQ') // Formato de moneda
                ->sortable(),
            Tables\Columns\TextColumn::make('precio_venta_sugerido')
                ->money('GTQ')
                ->label('Precio Venta'),
            Tables\Columns\TextColumn::make('max_perfiles')
                ->alignCenter(),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicios::route('/'),
            'create' => Pages\CreateServicio::route('/create'),
            'edit' => Pages\EditServicio::route('/{record}/edit'),
        ];
    }
}
