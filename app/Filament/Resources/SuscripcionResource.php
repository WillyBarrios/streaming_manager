<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuscripcionResource\Pages;
use App\Models\Suscripcion;
use App\Models\Perfil;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuscripcionResource extends Resource
{
    protected static ?string $model = Suscripcion::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationLabel = 'Suscripciones';
    protected static ?string $modelLabel = 'Suscripción';
    protected static ?string $pluralModelLabel = 'Suscripciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')->required(),
                        Forms\Components\TextInput::make('telefono')->required(),
                    ]),

                Forms\Components\Select::make('perfil_id')
                    ->label('Perfil a Vender')
                    ->options(function () {
                        return Perfil::where('estado', 'Disponible')
                            ->get()
                            ->pluck('nombre_completo', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->helperText('Solo aparecen perfiles marcados como Disponibles'),
                
                Forms\Components\TextInput::make('precio_pactado')
                    ->label('Precio Pactado')
                    ->prefix('Q')
                    ->numeric()
                    ->required(),

                Forms\Components\DatePicker::make('fecha_inicio')
                    ->default(now())
                    ->required(),

                Forms\Components\DatePicker::make('fecha_proximo_vencimiento')
                    ->label('Vence el')
                    ->default(now()->addDays(30))
                    ->required(),

                Forms\Components\Select::make('estado')
                    ->options([
                        'Activo' => 'Activo',
                        'Por Vencer' => 'Por Vencer',
                        'Vencido' => 'Vencido',
                        'Cancelado' => 'Cancelado',
                    ])
                    ->default('Activo')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['cliente', 'perfil.cuenta.servicio']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('perfil.cuenta.servicio.nombre')
                    ->label('Servicio')
                    ->description(fn (Suscripcion $record): string => $record->perfil->nombre_perfil)
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_pactado')
                    ->money('GTQ')
                    ->label('Precio'),

                Tables\Columns\TextColumn::make('fecha_proximo_vencimiento')
                    ->date('d M, Y')
                    ->sortable()
                    ->label('Vencimiento')
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activo' => 'success',
                        'Por Vencer' => 'warning',
                        'Vencido' => 'danger',
                        'Cancelado' => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Activo' => 'Activo',
                        'Vencido' => 'Vencido',
                    ]),
            ])
            ->actions([
                // Agrupamos las acciones en un menú desplegable (los 3 puntitos)
                Tables\Actions\ActionGroup::make([
                    
                    Tables\Actions\EditAction::make(),
                    
                    // --- AQUÍ ESTÁ EL BOTÓN DE RENOVAR ---
                    Tables\Actions\Action::make('renovar')
                        ->label('Renovar / Pagar')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Registrar Pago Mensual')
                        ->modalDescription('Esto registrará el pago y extenderá la fecha de vencimiento.')
                        ->form(function (Suscripcion $record) {
                            return [
                                Forms\Components\TextInput::make('monto')
                                    ->label('Monto Recibido')
                                    ->default($record->precio_pactado)
                                    ->required()
                                    ->prefix('Q')
                                    ->numeric(),
                                
                                Forms\Components\Select::make('meses')
                                    ->label('Meses a pagar')
                                    ->default(1)
                                    ->options([
                                        1 => '1 Mes',
                                        2 => '2 Meses',
                                        3 => '3 Meses',
                                        6 => '6 Meses',
                                        12 => '1 Año',
                                    ])
                                    ->required(),
                                    
                                Forms\Components\Select::make('metodo_pago')
                                    ->label('Método')
                                    ->options([
                                        'Transferencia' => 'Transferencia',
                                        'Efectivo' => 'Efectivo',
                                        'Deposito' => 'Depósito',
                                    ])
                                    ->default('Transferencia')
                                    ->required(),
                            ];
                        })
                        ->action(function (Suscripcion $record, array $data) {
                            // 1. Crear el Pago
                            \App\Models\Pago::create([
                                'suscripcion_id' => $record->id,
                                'monto' => $data['monto'],
                                'metodo_pago' => $data['metodo_pago'],
                                'fecha_pago' => now(),
                            ]);

                            // 2. Calcular nueva fecha
                            $nuevaFecha = $record->fecha_proximo_vencimiento < now() 
                                ? now()->addMonths($data['meses']) 
                                : \Carbon\Carbon::parse($record->fecha_proximo_vencimiento)->addMonths($data['meses']);

                            // 3. Actualizar Suscripción
                            $record->update([
                                'fecha_proximo_vencimiento' => $nuevaFecha,
                                'estado' => 'Activo',
                            ]);

                            // 4. Notificar
                            \Filament\Notifications\Notification::make()
                                ->title('¡Pago registrado y servicio renovado!')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuscripcions::route('/'),
            'create' => Pages\CreateSuscripcion::route('/create'),
            'edit' => Pages\EditSuscripcion::route('/{record}/edit'),
        ];
    }
}