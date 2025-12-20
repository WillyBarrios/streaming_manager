<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerfilResource\Pages;
use App\Models\Perfil;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PerfilResource extends Resource
{
    protected static ?string $model = Perfil::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    // CORRECCIÓN DE IDIOMA EN MENÚ
    protected static ?string $navigationLabel = 'Perfiles'; 
    protected static ?string $modelLabel = 'Perfil';
    protected static ?string $pluralModelLabel = 'Perfiles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Mostramos a qué cuenta pertenece (Solo lectura)
                Forms\Components\Select::make('cuenta_id')
                    ->relationship('cuenta', 'correo_acceso')
                    ->label('Cuenta Madre')
                    ->disabled() // No queremos mover un perfil de cuenta
                    ->required(),

                Forms\Components\TextInput::make('nombre_perfil')
                    ->label('Nombre del Perfil')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('pin')
                    ->label('PIN (4 Dígitos)')
                    ->numeric()
                    ->maxLength(4),

                Forms\Components\Select::make('estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Ocupado' => 'Ocupado',
                        'Mantenimiento' => 'Mantenimiento',
                    ])
                    ->required()
                    ->default('Disponible'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Aquí usamos la "magia" de las relaciones: Cuenta -> Servicio -> Nombre
                Tables\Columns\TextColumn::make('cuenta.servicio.nombre')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cuenta.correo_acceso')
                    ->label('Cuenta')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nombre_perfil')
                    ->label('Perfil')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pin')
                    ->label('PIN'),

                Tables\Columns\TextColumn::make('estado')
                    ->badge() // Lo mostramos como etiqueta de color
                    ->color(fn (string $state): string => match ($state) {
                        'Disponible' => 'success',
                        'Ocupado' => 'danger',
                        'Mantenimiento' => 'warning',
                    }),
            ])
            ->filters([
                // Filtro rápido para ver qué tienes libre para vender
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Ocupado' => 'Ocupado',
                    ]),
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
            'index' => Pages\ListPerfils::route('/'),
            'create' => Pages\CreatePerfil::route('/create'),
            'edit' => Pages\EditPerfil::route('/{record}/edit'),
        ];
    }
}