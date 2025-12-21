<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PagoResource\Pages;
use App\Models\Pago;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagoResource extends Resource
{
    protected static ?string $model = Pago::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Historial de Pagos';
    protected static ?string $pluralModelLabel = 'Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Buscamos la suscripción mostrando el nombre del cliente
                Forms\Components\Select::make('suscripcion_id')
                    ->relationship('suscripcion', 'id') // Truco: cargamos relación pero mostramos custom label abajo
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->cliente->nombre} - {$record->perfil->nombre_perfil}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Suscripción (Cliente - Perfil)'),

                Forms\Components\TextInput::make('monto')
                    ->required()
                    ->numeric()
                    ->prefix('Q'),

                Forms\Components\DateTimePicker::make('fecha_pago')
                    ->required()
                    ->default(now()),

                Forms\Components\Select::make('metodo_pago')
                    ->options([
                        'Transferencia' => 'Transferencia',
                        'Efectivo' => 'Efectivo',
                        'Deposito' => 'Depósito',
                    ])
                    ->required()
                    ->default('Transferencia'),

                Forms\Components\FileUpload::make('comprobante_url')
                    ->label('Comprobante (Foto)')
                    ->image()
                    ->directory('comprobantes')
                    ->visibility('public')
                    ->openable()      // Permite dar clic para abrir en pestaña nueva
                    ->downloadable()  // Permite descargar la imagen original
                    // -------------------------------
                    ->columnSpanFull(), // Opcional: Para que ocupe todo el ancho

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['suscripcion.cliente', 'suscripcion.perfil.cuenta.servicio']);
            })
            ->columns([
                // Columna 1: Cliente
                Tables\Columns\TextColumn::make('suscripcion.cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold'),

                // Columna 2: Qué pagó (Servicio)
                Tables\Columns\TextColumn::make('suscripcion.perfil.cuenta.servicio.nombre')
                    ->label('Servicio')
                    ->sortable()
                    ->badge() // Se ve bonito en burbuja
                    ->color('gray'),
                Tables\Columns\ImageColumn::make('comprobante_url')
                    ->label('Voucher')
                    ->visibility('public')
                    // Si le dan clic a la miniatura, abre la foto original en otra pestaña
                    ->url(fn ($record) => $record->comprobante_url ? \Illuminate\Support\Facades\Storage::url($record->comprobante_url) : null)
                    ->openUrlInNewTab(),

                // Columna 3: Monto
                Tables\Columns\TextColumn::make('monto')
                    ->money('GTQ')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                // Columna 4: Fecha
                Tables\Columns\TextColumn::make('fecha_pago')
                    ->dateTime('d M, Y H:i')
                    ->sortable()
                    ->label('Fecha'),

                // Columna 5: Método
                Tables\Columns\TextColumn::make('metodo_pago')
                    ->icon('heroicon-m-credit-card'),
            ])
            ->defaultSort('fecha_pago', 'desc') // Los más recientes primero
            ->filters([
                // Filtro por fecha para ver cuánto ganaste este mes
                Tables\Filters\Filter::make('fecha_pago')
                    ->form([
                        Forms\Components\DatePicker::make('desde'),
                        Forms\Components\DatePicker::make('hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date) => $query->whereDate('fecha_pago', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date) => $query->whereDate('fecha_pago', '<=', $date),
                            );
                    })
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPagos::route('/'),
            'create' => Pages\CreatePago::route('/create'),
            'edit' => Pages\EditPago::route('/{record}/edit'),
        ];
    }
}
