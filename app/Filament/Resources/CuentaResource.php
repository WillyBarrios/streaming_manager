<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuentaResource\Pages;
use App\Filament\Resources\CuentaResource\RelationManagers;
use App\Models\Cuenta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CuentaResource extends Resource
{
    protected static ?string $model = Cuenta::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

   public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Selector de Servicio
            Forms\Components\Select::make('servicio_id')
                ->relationship('servicio', 'nombre')
                ->required()
                ->preload()
                ->searchable()
                ->createOptionForm([
                    Forms\Components\TextInput::make('nombre')
                        ->required(),
                    Forms\Components\TextInput::make('precio_costo')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('precio_venta_sugerido')
                        ->numeric()
                        ->required(),
                ]),

            Forms\Components\TextInput::make('correo_acceso')
                ->email()
                ->required()
                ->label('Correo de la Cuenta'),

            Forms\Components\TextInput::make('contrasena')
                ->required()
                ->label('Contraseña'),

            Forms\Components\DatePicker::make('fecha_corte_proveedor')
                ->required()
                ->label('Fecha de Corte'),

            Forms\Components\Select::make('estado')
                ->options([
                    'Activa' => 'Activa',
                    'Suspendida' => 'Suspendida',
                    'Caida' => 'Caída',
                ])
                ->default('Activa')
                ->required(),
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
            'index' => Pages\ListCuentas::route('/'),
            'create' => Pages\CreateCuenta::route('/create'),
            'edit' => Pages\EditCuenta::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            // 1. Nombre del Servicio (Gracias a la relación servicio.nombre)
            Tables\Columns\TextColumn::make('servicio.nombre')
                ->label('Servicio')
                ->sortable()
                ->searchable()
                ->weight('bold'), // Pone el texto en negrita
                
            // 2. Correo de la cuenta
            Tables\Columns\TextColumn::make('correo_acceso')
                ->label('Correo / Usuario')
                ->searchable()
                ->copyable() // ¡Truco! Permite copiar el correo con un clic
                ->icon('heroicon-m-envelope'),
                
            // 3. Fecha de Corte (Formato bonito)
            Tables\Columns\TextColumn::make('fecha_corte_proveedor')
                ->label('Próximo Pago')
                ->date('d M, Y') // Ej: 25 Dic, 2025
                ->sortable()
                ->icon('heroicon-m-calendar'),

            // 4. Estado con colores (Badge moderno)
            Tables\Columns\TextColumn::make('estado')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Activa' => 'success',    // Verde
                    'Suspendida' => 'warning', // Amarillo
                    'Caida' => 'danger',      // Rojo
                }),
        ])
        ->filters([
            // Filtro para ver rápido las cuentas caídas
            Tables\Filters\SelectFilter::make('estado')
                ->options([
                    'Activa' => 'Activa',
                    'Suspendida' => 'Suspendida',
                    'Caida' => 'Caida',
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
}
