<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductNamePerCategoryTest extends TestCase
{
    use RefreshDatabase;

    private int $camisasHombre;
    private int $ninos;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::find(DB::table('users')->insertGetId([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin',
        ])));
        $hombre = DB::table('categories')->insertGetId(['name' => 'Hombre', 'description' => 'x', 'slug' => 'hombre']);
        $this->camisasHombre = DB::table('categories')->insertGetId(['name' => 'Camisas', 'description' => 'x', 'slug' => 'camisas', 'parent_category_id' => $hombre]);
        $this->ninos = DB::table('categories')->insertGetId(['name' => 'Niños', 'description' => 'x', 'slug' => 'ninos']);
    }

    private function crear(string $nombre, int $categoriaId)
    {
        return $this->postJson('/api/products', [
            'categoryId' => $categoriaId, 'name' => $nombre, 'description' => 'Camisa',
            'price' => 100, 'cost' => 50, 'saleType' => 'direct', 'status' => 'available',
        ]);
    }

    public function test_el_mismo_nombre_se_permite_en_categorias_distintas(): void
    {
        $this->crear('Camisa Roja', $this->camisasHombre)->assertCreated();
        $this->crear('Camisa Roja', $this->ninos)->assertCreated();

        $slugs = DB::table('products')->orderBy('id')->pluck('slug')->all();
        $this->assertSame(['camisa-roja', 'camisa-roja-2'], $slugs);
    }

    public function test_no_se_repite_el_nombre_dentro_de_la_misma_categoria(): void
    {
        $this->crear('Camisa Roja', $this->camisasHombre)->assertCreated();

        $this->crear('Camisa Roja', $this->camisasHombre)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name' => 'Ya existe un producto con este nombre en esta categoría.']);
    }

    public function test_un_producto_archivado_no_bloquea_el_nombre(): void
    {
        $id = $this->crear('Camisa Roja', $this->camisasHombre)->json('data.id');
        $this->deleteJson("/api/products/$id")->assertOk();

        $this->crear('Camisa Roja', $this->camisasHombre)->assertCreated();
    }

    // Crea "Camisa Roja" en la categoría y la pone en un pedido con el estado indicado
    private function productoEnPedido(string $estadoPedido, int $categoriaId): void
    {
        $productoId = $this->crear('Camisa Roja', $categoriaId)->assertCreated()->json('data.id');
        $estadoProducto = match ($estadoPedido) {
            'pending_payment' => 'reserved',
            'cancelled' => 'available',
            default => 'sold',
        };
        DB::table('products')->where('id', $productoId)->update(['status' => $estadoProducto]);

        $clienteId = DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => "cliente-$estadoPedido@test.com",
            'password' => 'x', 'email_verified_at' => now(),
        ]);
        $pedidoId = DB::table('orders')->insertGetId([
            'user_id' => $clienteId, 'shipping_address' => 'Zona 1', 'total' => 100, 'status' => $estadoPedido,
        ]);
        DB::table('order_items')->insert(['order_id' => $pedidoId, 'product_id' => $productoId, 'quantity' => 1, 'unit_price' => 100]);
    }

    public function test_un_producto_entregado_ya_no_bloquea_el_nombre(): void
    {
        $this->productoEnPedido('delivered', $this->camisasHombre);

        $this->crear('Camisa Roja', $this->camisasHombre)->assertCreated();
    }

    public function test_un_pedido_activo_o_cancelado_sigue_bloqueando_el_nombre(): void
    {
        // Pendiente, confirmado, en preparación o en camino: el producto sigue en un pedido activo.
        // Cancelado: el producto volvió a estar a la venta.
        foreach (['pending_payment', 'confirmed', 'preparing', 'on_route', 'cancelled'] as $estado) {
            $categoria = DB::table('categories')->insertGetId(['name' => "Cat $estado", 'description' => 'x', 'slug' => "cat-$estado"]);
            $this->productoEnPedido($estado, $categoria);

            $this->crear('Camisa Roja', $categoria)->assertStatus(422);
        }
    }

    public function test_editar_sin_cambiar_el_nombre_conserva_su_slug(): void
    {
        $this->crear('Camisa Roja', $this->camisasHombre)->assertCreated();
        $id = $this->crear('Camisa Roja', $this->ninos)->json('data.id');

        $this->post("/api/products/$id", [
            '_method' => 'PUT', 'categoryId' => $this->ninos, 'name' => 'Camisa Roja', 'description' => 'Editada',
            'price' => 120, 'cost' => 50, 'saleType' => 'direct', 'status' => 'available',
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame('camisa-roja-2', DB::table('products')->where('id', $id)->value('slug'));

        // Moverlo a la categoría donde ya hay otra "Camisa Roja" no se permite
        $this->post("/api/products/$id", [
            '_method' => 'PUT', 'categoryId' => $this->camisasHombre, 'name' => 'Camisa Roja', 'description' => 'Editada',
            'price' => 120, 'cost' => 50, 'saleType' => 'direct', 'status' => 'available',
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
