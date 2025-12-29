<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IngresosChart extends ChartWidget
{
    protected static ?string $heading = 'Tendencia de Ingresos (Últimos 6 Meses)';
    protected static ?int $sort = 2; // Debajo de las tarjetas de estadísticas
    protected int | string | array $columnSpan = 'full'; // Que ocupe todo el ancho

    protected function getData(): array
    {
        // 1. Consultamos los pagos de los últimos 6 meses, agrupados por mes
        $pagos = Pago::select(
                DB::raw('DATE_FORMAT(fecha_pago, "%Y-%m") as mes'),
                DB::raw('SUM(monto) as total')
            )
            ->where('fecha_pago', '>=', now()->subMonths(6)) // Solo últimos 6 meses
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->get();

        // 2. Preparamos las etiquetas (Ej: "2025-09", "2025-10")
        // Y formateamos para que se vea bonito (Ej: "Sep 2025")
        $labels = $pagos->map(function ($item) {
            return Carbon::createFromFormat('Y-m', $item->mes)->translatedFormat('M Y');
        });

        // 3. Preparamos los datos (Los montos de dinero)
        $data = $pagos->pluck('total');

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos Cobrados (Q)',
                    'data' => $data,
                    'borderColor' => '#10b981', // Verde esmeralda (dinero)
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)', // Sombreado suave
                    'fill' => true,
                    'tension' => 0.4, // Hace la línea curva y suave
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
