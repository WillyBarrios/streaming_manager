<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Suscripcion;

class ProximosVencimientos extends BaseWidget
{
    protected static ?int $sort = 4; // Al final del dashboard
    protected int | string | array $columnSpan = 'full'; // Ancho completo
    protected static ?string $heading = '⚠️ Vencimientos Próximos (5 Días)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Suscripcion::query()
                    ->with(['cliente', 'perfil.cuenta.servicio'])
                    ->where('estado', 'Activo')
                    // Filtro Mágico: Desde HOY hasta dentro de 5 días
                    ->whereBetween('fecha_proximo_vencimiento', [now(), now()->addDays(5)])
                    ->orderBy('fecha_proximo_vencimiento', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('perfil.nombre_completo')
                    ->label('Servicio'),

                Tables\Columns\TextColumn::make('fecha_proximo_vencimiento')
                    ->label('Vence')
                    ->date('d M (l)') // Ej: 25 Dic (Lunes)
                    ->color('warning'), // Amarillo de alerta

                Tables\Columns\TextColumn::make('dias_restantes')
                    ->label('Falta')
                    ->state(fn (Suscripcion $record) => now()->diffInDays($record->fecha_proximo_vencimiento) . ' días')
                    ->badge()
                    ->color('warning'),
            ])
            ->actions([
                // Botón rápido para avisar por WhatsApp
                Tables\Actions\Action::make('avisar')
                    ->label('Recordar')
                    ->icon('heroicon-m-chat-bubble-left')
                    ->color('success')
                    ->url(fn (Suscripcion $record) => 'https://wa.me/' . $record->cliente->telefono . '?text=' . urlencode(
                        "Hola {$record->cliente->nombre}, paso a recordarte que tu servicio de {$record->perfil->cuenta->servicio->nombre} vence el " .
                        $record->fecha_proximo_vencimiento->format('d/m') . ". ¿Te gustaría renovarlo?"
                    ), shouldOpenInNewTab: true),
            ])
            ->paginated(false); // Lista compacta sin páginas
    }
}
