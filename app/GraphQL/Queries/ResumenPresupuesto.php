<?php

namespace App\GraphQL\Queries;

use App\Models\Cuenta;
use App\Models\Pago;
use Carbon\CarbonImmutable;

final class ResumenPresupuesto
{
    /**
     * @return array{
     *     periodo: string,
     *     gastos_suscripciones: string,
     *     ingresos_clientes: string,
     *     saldo: string
     * }
     */
    public function __invoke(mixed $_, array $args): array
    {
        $inicio = CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        $fin = $inicio->endOfMonth();

        $gastos = (string) Cuenta::query()
            ->where('cuentas.estado', 'Activa')
            ->join('servicios', 'servicios.id', '=', 'cuentas.servicio_id')
            ->sum('servicios.precio_costo');

        $ingresos = (string) Pago::query()
            ->whereBetween('fecha_pago', [$inicio, $fin])
            ->sum('monto');

        $gastosCentavos = $this->toCents($gastos);
        $ingresosCentavos = $this->toCents($ingresos);

        return [
            'periodo' => $inicio->format('Y-m'),
            'gastos_suscripciones' => $this->formatCents($gastosCentavos),
            'ingresos_clientes' => $this->formatCents($ingresosCentavos),
            'saldo' => $this->formatCents($ingresosCentavos - $gastosCentavos),
        ];
    }

    private function toCents(string $amount): int
    {
        $amount = trim($amount);
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '+-');

        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '');
        $decimal = substr(str_pad($decimal, 2, '0'), 0, 2);
        $cents = ((int) $integer * 100) + (int) $decimal;

        return $negative ? -$cents : $cents;
    }

    private function formatCents(int $cents): string
    {
        $negative = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf(
            '%s%d.%02d',
            $negative,
            intdiv($absolute, 100),
            $absolute % 100,
        );
    }
}
