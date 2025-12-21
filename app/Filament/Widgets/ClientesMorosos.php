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
                Suscripcion::query()
                    ->with(['cliente', 'perfil.cuenta.servicio'])
                    ->where('estado', 'Activo')
                    ->where('fecha_proximo_vencimiento', '<', now())
            )
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('perfil.nombre_completo')
                    ->label('Servicio')
                    ->limit(20),

                Tables\Columns\TextColumn::make('fecha_proximo_vencimiento')
                    ->label('Venció')
                    ->date('d M, Y')
                    ->color('danger'),

                // COLUMNA 1: Meses de Atraso (Calculado)
                Tables\Columns\TextColumn::make('meses_atraso')
                    ->label('Meses Pend.')
                    ->state(function (Suscripcion $record) {
                        // Calculamos cuántos meses han pasado desde el vencimiento hasta hoy
                        // Le sumamos 1 porque si venció en septiembre, septiembre cuenta como deuda
                        $meses = now()->diffInMonths($record->fecha_proximo_vencimiento);
                        return abs($meses) + 1;
                    })
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                // COLUMNA 2: Deuda Total (Precio * Meses)
                Tables\Columns\TextColumn::make('deuda_total')
                    ->label('Total a Pagar')
                    ->money('GTQ')
                    ->weight('black') // Letra bien gruesa
                    ->color('danger')
                    ->state(function (Suscripcion $record) {
                        $meses = now()->diffInMonths($record->fecha_proximo_vencimiento);
                        $totalMeses = abs($meses) + 1;

                        return $record->precio_pactado * $totalMeses;
                    }),
            ])
            ->actions([
                // BOTÓN WHATSAPP INTELIGENTE (Cobra el total acumulado)
                Tables\Actions\Action::make('whatsapp')
                    ->label('Cobrar Todo')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (Suscripcion $record) {
                        $meses = now()->diffInMonths($record->fecha_proximo_vencimiento);
                        $totalMeses = abs($meses) + 1;
                        $totalDeuda = $record->precio_pactado * $totalMeses;

                        return 'https://wa.me/' . $record->cliente->telefono . '?text=' . urlencode(
                            "Hola {$record->cliente->nombre}, notamos que tu suscripción de {$record->perfil->cuenta->servicio->nombre} venció el " .
                            $record->fecha_proximo_vencimiento->format('d/m/Y') . ". " .
                            "Tienes {$totalMeses} meses pendientes. " .
                            "El total a pagar para ponerte al día es de Q.{$totalDeuda}. ¿Me confirmas tu pago?"
                        );
                    }, shouldOpenInNewTab: true),

                Tables\Actions\Action::make('ir_a_pagar')
                    ->label('Gestionar')
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (Suscripcion $record) => \App\Filament\Resources\SuscripcionResource::getUrl('index')),
            ]);
    }
}
