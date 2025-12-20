<?php

namespace App\Filament\Widgets;

use App\Models\Suscripcion;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ClientesMorosos extends BaseWidget
{
    // Hacemos que ocupe todo el ancho de la pantalla
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2; 

    protected function getTableHeading(): string
    {
        return '🚨 Clientes con Pagos Atrasados';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // CORRECCIÓN: Usamos query() explícitamente y luego los filtros
                Suscripcion::query()
                    ->with(['cliente', 'perfil.cuenta.servicio'])
                    ->where('estado', 'Activo')
                    ->where('fecha_proximo_vencimiento', '<', now())
            )
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('perfil.nombre_completo')
                    ->label('Servicio / Perfil')
                    ->limit(30),

                Tables\Columns\TextColumn::make('fecha_proximo_vencimiento')
                    ->label('Venció el')
                    ->date('d M, Y')
                    ->color('danger'), 

                Tables\Columns\TextColumn::make('precio_pactado')
                    ->label('Debe')
                    ->money('GTQ')
                    ->weight('bold'),

                // Calculamos días de retraso
                Tables\Columns\TextColumn::make('dias_retraso')
                    ->label('Días Atraso')
                    ->state(fn (Suscripcion $record) => round(now()->diffInDays($record->fecha_proximo_vencimiento)))
                    ->badge()
                    ->color('danger'),
            ])
            ->actions([
                // Botón de WhatsApp
                Tables\Actions\Action::make('whatsapp')
                    ->label('Cobrar')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Suscripcion $record) => 
                        'https://wa.me/' . $record->cliente->telefono . '?text=' . urlencode(
                            "Hola {$record->cliente->nombre}, tu servicio de {$record->perfil->cuenta->servicio->nombre} venció el " . 
                            $record->fecha_proximo_vencimiento->format('d/m') . ". Son Q.{$record->precio_pactado}. ¿Me ayudas con el pago?"
                        )
                    , shouldOpenInNewTab: true),
                    
                // Atajo para ir a pagar
                Tables\Actions\Action::make('ir_a_pagar')
                    ->label('Gestionar')
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (Suscripcion $record) => \App\Filament\Resources\SuscripcionResource::getUrl('index')),
            ]);
    }
}