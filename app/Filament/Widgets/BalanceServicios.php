<?php

namespace App\Filament\Widgets;

use App\Models\Servicio;
use App\Models\Pago;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class BalanceServicios extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return '💰 Balance Financiero (Mes Actual)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // CORRECCIÓN 1: Usamos withCount aquí para cargar el conteo de una vez
                // Así evitamos llamar a la función ->cuentas() dentro de los bucles
                Servicio::query()->withCount('cuentas')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->weight('bold')
                    ->label('Servicio'),

                Tables\Columns\TextColumn::make('cuentas_count')
                    ->label('Cuentas')
                    ->alignCenter(),

                // 1. COLUMNA GASTO (Con Total)
                Tables\Columns\TextColumn::make('gasto_mensual')
                    ->label('Gasto Operativo')
                    ->money('GTQ')
                    // CORRECCIÓN 2: Usamos la propiedad 'cuentas_count' en lugar de la función
                    ->state(fn ($record) => $record->precio_costo * $record->cuentas_count)
                    ->color('danger')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Gastos')
                            ->money('GTQ')
                            ->using(fn ($query) => $query->get()->sum(fn ($record) => $record->precio_costo * $record->cuentas_count))
                    ),

                // 2. COLUMNA INGRESOS (Con Total)
                Tables\Columns\TextColumn::make('ingresos_mes')
                    ->label('Cobrado este Mes')
                    ->money('GTQ')
                    ->state(function ($record) {
                        return Pago::whereHas('suscripcion.perfil.cuenta', function($q) use ($record){
                            $q->where('servicio_id', $record->id);
                        })
                        ->whereMonth('fecha_pago', Carbon::now()->month)
                        ->whereYear('fecha_pago', Carbon::now()->year)
                        ->sum('monto');
                    })
                    ->color('success')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Ingresos')
                            ->money('GTQ')
                            // Calculamos el total global de pagos del mes directamente
                            ->using(fn () => Pago::whereMonth('fecha_pago', Carbon::now()->month)->whereYear('fecha_pago', Carbon::now()->year)->sum('monto'))
                    ),

                // 3. COLUMNA UTILIDAD (Con Total)
                Tables\Columns\TextColumn::make('utilidad')
                    ->label('Utilidad Neta')
                    ->money('GTQ')
                    ->weight('bold')
                    ->state(function ($record) {
                        $gasto = $record->precio_costo * $record->cuentas_count;

                        $ingreso = Pago::whereHas('suscripcion.perfil.cuenta', function($q) use ($record){
                            $q->where('servicio_id', $record->id);
                        })
                        ->whereMonth('fecha_pago', Carbon::now()->month)
                        ->whereYear('fecha_pago', Carbon::now()->year)
                        ->sum('monto');

                        return $ingreso - $gasto;
                    })
                    ->color(fn (string $state): string => $state < 0 ? 'danger' : 'success')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Neto Final')
                            ->money('GTQ')
                            ->using(function ($query) {
                                // 1. Calcular Gasto Total usando cuentas_count
                                $gastoTotal = $query->get()->sum(fn ($r) => $r->precio_costo * $r->cuentas_count);

                                // 2. Calcular Ingreso Total Global
                                $ingresoTotal = Pago::whereMonth('fecha_pago', Carbon::now()->month)
                                    ->whereYear('fecha_pago', Carbon::now()->year)
                                    ->sum('monto');

                                return $ingresoTotal - $gastoTotal;
                            })
                    ),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Balance')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('success')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('Balance_General_' . date('Y-m-d')),
                    ]),
            ])
            ->paginated(false);
    }
}
