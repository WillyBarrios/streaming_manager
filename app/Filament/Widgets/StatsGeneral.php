<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Suscripcion;

class StatsGeneral extends BaseWidget
{
    // Esto define qué tan rápido se refresca (opcional, aquí no lo pondremos para no cargar el sistema)
    protected static ?int $sort = 1; // Para que salga mero arriba

    protected function getStats(): array
    {
        // 1. Clientes Activos (Total de suscripciones)
        $totalSuscripciones = Suscripcion::where('estado', 'Activo')->count();

        // 2. Ingreso Mensual Proyectado (La suma de todos los precios de tus suscripciones activas)
        $ingresoMensual = Suscripcion::where('estado', 'Activo')->sum('precio_pactado');

        // 3. Cálculo de Deuda Total (Dinero en la calle)
        // Usamos la misma lógica precisa que en tu tabla de morosos
        $suscripcionesVencidas = Suscripcion::where('estado', 'Activo')
            ->where('fecha_proximo_vencimiento', '<', now())
            ->get();

        $deudaTotal = 0;
        foreach ($suscripcionesVencidas as $record) {
             // Calculamos meses exactos + 1 (el mes corriente)
             $meses = intval(abs(now()->diffInMonths($record->fecha_proximo_vencimiento))) + 1;
             $deudaTotal += ($record->precio_pactado * $meses);
        }

        return [
            Stat::make('Suscripciones Activas', $totalSuscripciones)
                ->description('Servicios vendidos')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Gráfico decorativo
                ->color('success'),

            Stat::make('Ingreso Mensual Estimado', 'Q ' . number_format($ingresoMensual, 2))
                ->description('Facturación ideal mensual')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Dinero en la Calle', 'Q ' . number_format($deudaTotal, 2))
                ->description('Total acumulado en mora')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
