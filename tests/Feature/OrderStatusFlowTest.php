<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CambioEstadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->admin = User::find(DB::table('users')->insertGetId([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin',
        ]));
        $this->cliente = User::find(DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => 'cliente@test.com',
            'password' => 'x', 'email_verified_at' => now(),
        ]));
        Sanctum::actingAs($this->admin);
    }

    private function pedido(string $estado = 'pending_payment', $confirmadoEn = null): int
    {
        return DB::table('orders')->insertGetId([
            'user_id' => $this->cliente->id, 'shipping_address' => 'Zona 1', 'total' => 200,
            'status' => $estado, 'confirmed_at' => $confirmadoEn, 'created_at' => now(),
        ]);
    }

    private function cambiarEstado(int $orderId, string $estado)
    {
        return $this->patchJson("/api/orders/$orderId/status", ['status' => $estado]);
    }

    public function test_un_pedido_confirmado_no_puede_regresar_a_pendiente_de_pago(): void
    {
        $id = $this->pedido();
        $this->cambiarEstado($id, 'confirmed')->assertOk();

        foreach (['confirmed', 'preparing', 'on_route'] as $estado) {
            DB::table('orders')->where('id', $id)->update(['status' => $estado]);
            $this->cambiarEstado($id, 'pending_payment')
                ->assertStatus(422)
                ->assertJsonPath('message', 'Un pedido confirmado no puede regresar a "Pendiente de pago". Si el pago no fue válido, cancélalo.');
        }
    }

    public function test_se_puede_ir_y_venir_entre_etapas_sin_mover_la_fecha_de_confirmacion(): void
    {
        $id = $this->pedido();
        $this->cambiarEstado($id, 'confirmed')->assertOk();
        $fecha = DB::table('orders')->find($id)->confirmed_at;
        $this->assertNotNull($fecha);

        $this->travel(2)->days();
        $this->cambiarEstado($id, 'on_route')->assertOk();
        $this->cambiarEstado($id, 'preparing')->assertOk();
        $this->cambiarEstado($id, 'confirmed')->assertOk();

        $this->assertSame($fecha, DB::table('orders')->find($id)->confirmed_at);
    }

    public function test_entregado_y_cancelado_son_finales(): void
    {
        $this->cambiarEstado($this->pedido('delivered', now()), 'on_route')->assertStatus(422);
        $this->cambiarEstado($this->pedido('cancelled'), 'confirmed')->assertStatus(422);
    }

    public function test_guardar_el_mismo_estado_no_vuelve_a_notificar_al_cliente(): void
    {
        $id = $this->pedido();
        $this->cambiarEstado($id, 'confirmed')->assertOk();
        $this->cambiarEstado($id, 'confirmed')->assertOk();

        Notification::assertSentToTimes($this->cliente, CambioEstadoNotification::class, 1);
    }

    public function test_la_tarjeta_de_ingresos_del_mes_no_cuenta_pedidos_pendientes_de_pago(): void
    {
        $this->pedido('confirmed', now());
        $this->pedido('pending_payment', now()); // pedido antiguo que se regresó a pendiente

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.monthlyRevenue', 200);
    }
}
