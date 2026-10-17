<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsaDataWarehouse;
use Tests\TestCase;

class BitacoraTest extends TestCase
{
    use RefreshDatabase, UsaDataWarehouse;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararDataWarehouse();
        Notification::fake();

        $this->admin = User::find(DB::table('users')->insertGetId([
            'name' => 'Ana', 'last_name' => 'Admin', 'email' => 'ana@tienda.com',
            'password' => bcrypt('Secreta123!'), 'email_verified_at' => now(), 'role' => 'admin',
        ]));
    }

    private function pedido(): int
    {
        $cliente = DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => 'cliente@test.com', 'password' => 'x', 'email_verified_at' => now(),
        ]);
        return DB::table('orders')->insertGetId([
            'user_id' => $cliente, 'shipping_address' => 'Zona 1', 'total' => 100,
            'status' => 'pending_payment', 'public_code' => 'AB12CD', 'created_at' => now(),
        ]);
    }

    public function test_registra_quien_cambio_el_estado_de_un_pedido_y_como_estaba_antes(): void
    {
        $pedido = $this->pedido();
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/orders/$pedido/status", ['status' => 'confirmed'])->assertOk();

        $fila = $this->dw()->table('bitacora')->where('accion', 'pedido.estado_cambiado')->first();
        $this->assertNotNull($fila);
        $this->assertSame('ana@tienda.com', $fila->usuario_correo);
        $this->assertSame('admin', $fila->usuario_rol);
        $this->assertSame((string) $pedido, $fila->entidad_id);
        $this->assertSame('Cambió el pedido AB12CD de "Pendiente de pago" a "Confirmado"', $fila->descripcion);
        $this->assertSame('pending_payment', json_decode($fila->datos_antes, true)['status']);
        $this->assertSame(['status' => 'confirmed'], json_decode($fila->datos_despues, true));
    }

    public function test_registra_inicios_de_sesion_exitosos_y_fallidos_sin_guardar_la_contrasena(): void
    {
        $this->postJson('/api/login', ['email' => 'ana@tienda.com', 'password' => 'incorrecta1'])->assertStatus(401);
        $this->postJson('/api/login', ['email' => 'ana@tienda.com', 'password' => 'Secreta123!'])->assertOk();

        $filas = $this->dw()->table('bitacora')->orderBy('id')->get();
        $this->assertSame(['sesion.inicio_fallida', 'sesion.inicio'], $filas->pluck('accion')->all());
        $this->assertSame(['rechazado', 'exito'], $filas->pluck('resultado')->all());
        $this->assertSame($this->admin->id, (int) $filas[1]->usuario_id);

        $todo = json_encode($filas);
        $this->assertStringNotContainsString('Secreta123!', $todo);
        $this->assertStringNotContainsString('incorrecta1', $todo);
    }

    public function test_las_acciones_rechazadas_del_admin_no_se_registran(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/api/categories', [])->assertStatus(422); // validación fallida

        $this->assertSame(0, $this->dw()->table('bitacora')->count());
    }

    public function test_la_bitacora_no_se_puede_modificar_ni_borrar(): void
    {
        $this->postJson('/api/login', ['email' => 'ana@tienda.com', 'password' => 'Secreta123!'])->assertOk();

        foreach ([fn() => $this->dw()->table('bitacora')->update(['descripcion' => 'alterado']),
                  fn() => $this->dw()->table('bitacora')->delete()] as $intento) {
            try {
                $intento();
                $this->fail('La bitácora permitió modificarse');
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertStringContainsString('inmutable', $e->getMessage());
            }
        }
        $this->assertSame(1, $this->dw()->table('bitacora')->count());
    }

    public function test_el_admin_consulta_la_bitacora_con_filtros_y_un_cliente_no(): void
    {
        $pedido = $this->pedido();
        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/orders/$pedido/status", ['status' => 'confirmed'])->assertOk();
        $this->postJson('/api/categories', ['name' => 'Hombre', 'description' => 'x'])->assertCreated();

        $this->getJson('/api/admin/bitacora?accion=categoria.creada')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.descripcion', 'Creó la categoría "Hombre"')
            ->assertJsonFragment(['acciones' => ['categoria.creada', 'pedido.estado_cambiado']]);

        Sanctum::actingAs(User::find(DB::table('users')->where('role', '!=', 'admin')->value('id')));
        $this->getJson('/api/admin/bitacora')->assertForbidden();
    }

    public function test_valida_el_rango_de_fechas_como_el_dashboard(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/bitacora?desde=2026-10-10&hasta=2026-10-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hasta' => 'La fecha "Desde" no puede ser posterior a la fecha "Hasta".']);

        $manana = now('America/Guatemala')->addDays(2)->format('Y-m-d');
        $this->getJson("/api/admin/bitacora?hasta=$manana")->assertStatus(422)->assertJsonValidationErrors(['hasta']);

        $this->getJson('/api/admin/bitacora?desde=2026-10-01&hasta=2026-10-01')->assertOk();
    }

    public function test_si_la_bitacora_falla_la_operacion_sigue_funcionando(): void
    {
        $pedido = $this->pedido();
        Sanctum::actingAs($this->admin);
        $this->dw()->getSchemaBuilder()->drop('bitacora'); // simula el warehouse caído

        $this->patchJson("/api/orders/$pedido/status", ['status' => 'confirmed'])->assertOk();
        $this->assertSame('confirmed', DB::table('orders')->where('id', $pedido)->value('status'));
    }
}
