<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\Suscripcion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetGraphqlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_graphql_endpoint_requires_a_sanctum_token(): void
    {
        $this->postJson('/graphql', [
            'query' => 'query { resumenPresupuesto { saldo } }',
        ])->assertUnauthorized();
    }

    public function test_it_lists_active_provider_accounts_and_calculates_the_budget(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');
        Sanctum::actingAs(User::factory()->create());

        $activeService = Servicio::query()->create([
            'nombre' => 'Netflix',
            'precio_costo' => '15.50',
            'precio_venta_sugerido' => '25.00',
            'max_perfiles' => 1,
        ]);

        $activeAccount = Cuenta::query()->create([
            'servicio_id' => $activeService->id,
            'correo_acceso' => 'netflix@cinematv.test',
            'contrasena' => 'encrypted-value',
            'fecha_corte_proveedor' => '2026-08-20',
            'estado' => 'Activa',
        ]);

        $inactiveService = Servicio::query()->create([
            'nombre' => 'Max',
            'precio_costo' => '9.99',
            'precio_venta_sugerido' => '18.00',
            'max_perfiles' => 1,
        ]);

        Cuenta::query()->create([
            'servicio_id' => $inactiveService->id,
            'correo_acceso' => 'max@cinematv.test',
            'contrasena' => 'encrypted-value',
            'fecha_corte_proveedor' => '2026-08-21',
            'estado' => 'Suspendida',
        ]);

        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente móvil',
            'telefono' => '55550000',
        ]);

        $suscripcion = Suscripcion::query()->create([
            'cliente_id' => $cliente->id,
            'perfil_id' => $activeAccount->perfiles()->firstOrFail()->id,
            'precio_pactado' => '25.00',
            'fecha_inicio' => '2026-08-01',
            'fecha_proximo_vencimiento' => '2026-08-31',
            'estado' => 'Activo',
        ]);

        Pago::query()->create([
            'suscripcion_id' => $suscripcion->id,
            'monto' => '25.00',
            'fecha_pago' => '2026-08-10 09:00:00',
            'metodo_pago' => 'Transferencia',
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
                query Dashboard {
                    suscripcionesActivas {
                        id
                        nombre_servicio
                        costo_mensual
                        fecha_proximo_pago
                        estado
                    }
                    pagosRecientes(first: 10) {
                        data {
                            cliente_id
                            monto
                            tipo_movimiento
                        }
                    }
                    resumenPresupuesto {
                        periodo
                        gastos_suscripciones
                        ingresos_clientes
                        saldo
                    }
                }
                GRAPHQL,
        ]);

        $response
            ->assertOk()
            ->assertJsonMissingPath('errors')
            ->assertJsonCount(1, 'data.suscripcionesActivas')
            ->assertJsonPath('data.suscripcionesActivas.0.nombre_servicio', 'Netflix')
            ->assertJsonPath('data.suscripcionesActivas.0.costo_mensual', '15.50')
            ->assertJsonPath('data.pagosRecientes.data.0.cliente_id', (string) $cliente->id)
            ->assertJsonPath('data.pagosRecientes.data.0.tipo_movimiento', 'INGRESO')
            ->assertJsonPath('data.resumenPresupuesto.periodo', '2026-08')
            ->assertJsonPath('data.resumenPresupuesto.gastos_suscripciones', '15.50')
            ->assertJsonPath('data.resumenPresupuesto.ingresos_clientes', '25.00')
            ->assertJsonPath('data.resumenPresupuesto.saldo', '9.50');
    }

    public function test_it_registers_a_payment_and_updates_a_client_subscription(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $service = Servicio::query()->create([
            'nombre' => 'Disney+',
            'precio_costo' => '12.00',
            'precio_venta_sugerido' => '20.00',
            'max_perfiles' => 1,
        ]);

        $account = Cuenta::query()->create([
            'servicio_id' => $service->id,
            'correo_acceso' => 'disney@cinematv.test',
            'contrasena' => 'encrypted-value',
            'fecha_corte_proveedor' => '2026-08-22',
            'estado' => 'Activa',
        ]);

        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente API',
            'telefono' => '55551111',
        ]);

        $suscripcion = Suscripcion::query()->create([
            'cliente_id' => $cliente->id,
            'perfil_id' => $account->perfiles()->firstOrFail()->id,
            'precio_pactado' => '20.00',
            'fecha_inicio' => '2026-08-01',
            'fecha_proximo_vencimiento' => '2026-08-31',
            'estado' => 'Activo',
        ]);

        $paymentResponse = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
                mutation RegistrarPago($input: RegistrarPagoInput!) {
                    registrarPago(input: $input) {
                        suscripcion_id
                        cliente_id
                        monto
                        metodo_pago
                        tipo_movimiento
                    }
                }
                GRAPHQL,
            'variables' => [
                'input' => [
                    'suscripcion_id' => (string) $suscripcion->id,
                    'monto' => '20.00',
                    'metodo_pago' => 'EFECTIVO',
                    'fecha_pago' => '2026-08-15 12:00:00',
                ],
            ],
        ]);

        $paymentResponse
            ->assertOk()
            ->assertJsonMissingPath('errors')
            ->assertJsonPath('data.registrarPago.cliente_id', (string) $cliente->id)
            ->assertJsonPath('data.registrarPago.monto', '20.00')
            ->assertJsonPath('data.registrarPago.metodo_pago', 'EFECTIVO');

        $this->assertDatabaseHas('pagos', [
            'suscripcion_id' => $suscripcion->id,
            'monto' => 20,
            'metodo_pago' => 'Efectivo',
        ]);

        $updateResponse = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
                mutation ActualizarSuscripcion($input: ActualizarSuscripcionInput!) {
                    actualizarSuscripcion(input: $input) {
                        id
                        estado
                    }
                }
                GRAPHQL,
            'variables' => [
                'input' => [
                    'id' => (string) $suscripcion->id,
                    'estado' => 'CANCELADO',
                ],
            ],
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonMissingPath('errors')
            ->assertJsonPath('data.actualizarSuscripcion.estado', 'CANCELADO');

        $this->assertDatabaseHas('suscripciones', [
            'id' => $suscripcion->id,
            'estado' => 'Cancelado',
        ]);
    }
}
