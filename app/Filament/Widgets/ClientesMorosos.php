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
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('perfil.nombre_completo')
                    ->label('Servicio')
                    ->limit(20),

                Tables\Columns\TextColumn::make('fecha_proximo_vencimiento')
                    ->label('Venció')
                    ->date('d M, Y')
                    ->color('danger'),

                // CORRECCIÓN 1: Forzamos número entero para que no salgan decimales
                Tables\Columns\TextColumn::make('meses_atraso')
                    ->label('Meses')
                    ->state(function (Suscripcion $record) {
                        // diffInMonths devuelve entero por defecto, pero por si acaso usamos intval
                        // abs() es para que salga positivo
                        // +1 para contar el mes en curso como deuda
                        return intval(abs(now()->diffInMonths($record->fecha_proximo_vencimiento))) + 1;
                    })
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('deuda_fila')
                    ->label('Subtotal')
                    ->money('GTQ')
                    ->state(function (Suscripcion $record) {
                        $meses = intval(abs(now()->diffInMonths($record->fecha_proximo_vencimiento))) + 1;
                        return $record->precio_pactado * $meses;
                    }),
            ])
            ->actions([
                // CORRECCIÓN 2: Botón de WhatsApp con "Deuda Global"
                Tables\Actions\Action::make('whatsapp')
                    ->label('Cobrar Todo')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (Suscripcion $record) {
                        // 1. Identificamos al cliente
                        $clienteId = $record->cliente_id;

                        // 2. Buscamos TODAS las suscripciones vencidas de ESTE cliente
                        $todasLasDeudas = Suscripcion::where('cliente_id', $clienteId)
                            ->where('estado', 'Activo')
                            ->where('fecha_proximo_vencimiento', '<', now())
                            ->get();

                        // 3. Sumamos el total global
                        $granTotal = 0;
                        $serviciosLista = [];

                        foreach ($todasLasDeudas as $deuda) {
                            $meses = intval(abs(now()->diffInMonths($deuda->fecha_proximo_vencimiento))) + 1;
                            $monto = $deuda->precio_pactado * $meses;

                            $granTotal += $monto;
                            // Guardamos nombre del servicio para el mensaje (opcional)
                            $serviciosLista[] = $deuda->perfil->cuenta->servicio->nombre;
                        }

                        // Quitamos duplicados de nombres de servicios por si tiene 2 de Netflix
                        $nombresServicios = implode(', ', array_unique($serviciosLista));

                        // 4. Armamos el mensaje final
                        return 'https://wa.me/' . $record->cliente->telefono . '?text=' . urlencode(
                            "Hola {$record->cliente->nombre}, tienes pagos pendientes en tus servicios: ({$nombresServicios}). " .
                            "El saldo TOTAL acumulado a la fecha es de Q.{$granTotal}. " .
                            "¿Podrías apoyarme con el pago?"
                        );
                    }, shouldOpenInNewTab: true),

                Tables\Actions\Action::make('ir_a_pagar')
                    ->label('Gestionar')
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (Suscripcion $record) => \App\Filament\Resources\SuscripcionResource::getUrl('index')),
            ]);
    }
}
