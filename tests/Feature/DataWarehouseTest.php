<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsaDataWarehouse;
use Tests\TestCase;

class DataWarehouseTest extends TestCase
{
    use RefreshDatabase, UsaDataWarehouse;

    private int $camisas;
    private int $botas;
    private int $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararDataWarehouse();
        // Mediados de octubre de 2026 en Guatemala
        $this->travelTo(Carbon::parse('2026-10-16 12:00:00', 'America/Guatemala'));

        $hombre = DB::table('categories')->insertGetId(['name' => 'Hombre', 'description' => 'x', 'slug' => 'hombre']);
        $this->camisas = DB::table('categories')->insertGetId(['name' => 'Camisas', 'description' => 'x', 'slug' => 'camisas', 'parent_category_id' => $hombre]);
        $this->botas = DB::table('categories')->insertGetId(['name' => 'Calzado', 'description' => 'x', 'slug' => 'calzado']);
        $this->cliente = DB::table('users')->insertGetId([
            'name' => 'Luis', 'last_name' => 'Pérez', 'email' => 'luis@test.com', 'password' => 'x',
            'email_verified_at' => now(), 'provider' => 'google', 'created_at' => '2026-09-01 10:00:00',
        ]);
    }

    private function producto(string $nombre, int $categoria, float $costo, string $publicado = '2026-10-01 10:00:00', string $tipo = 'direct'): int
    {
        return DB::table('products')->insertGetId([
            'category_id' => $categoria, 'name' => $nombre, 'slug' => str()->slug($nombre), 'description' => 'x',
            'price' => 100, 'cost' => $costo, 'sale_type' => $tipo, 'status' => 'sold', 'created_at' => $publicado,
        ]);
    }

    // Pedido confirmado en una fecha UTC exacta
    private function venta(int $producto, float $precio, float $costo, string $confirmadoUtc, string $estado = 'confirmed'): int
    {
        $pedido = DB::table('orders')->insertGetId([
            'user_id' => $this->cliente, 'shipping_address' => 'Zona 1', 'total' => $precio, 'status' => $estado,
            'public_code' => strtoupper(str()->random(6)), 'confirmed_at' => $confirmadoUtc, 'created_at' => $confirmadoUtc, 'updated_at' => $confirmadoUtc,
        ]);
        DB::table('order_items')->insert(['order_id' => $pedido, 'product_id' => $producto, 'quantity' => 1, 'unit_price' => $precio, 'unit_cost' => $costo]);
        return $pedido;
    }

    public function test_carga_las_ventas_en_el_esquema_estrella_con_el_dia_de_guatemala(): void
    {
        $camisa = $this->producto('Camisa Roja', $this->camisas, 80, '2026-10-01 10:00:00');
        // 11 oct 02:00 UTC = sábado 10 oct 8:00 p. m. en Guatemala
        $this->venta($camisa, 150, 80, '2026-10-11 02:00:00');

        $this->artisan('dw:cargar')->assertSuccessful();

        $venta = $this->dw()->table('hechos_ventas')->first();
        $this->assertSame(20261010, (int) $venta->fecha_id);
        $this->assertSame('venta', $venta->tipo_movimiento);
        $this->assertEquals(150, $venta->ingreso);
        $this->assertEquals(70, $venta->ganancia);
        $this->assertSame(9, (int) $venta->dias_para_vender);

        $fecha = $this->dw()->table('dim_fecha')->where('fecha_id', 20261010)->first();
        $this->assertSame('Sábado', $fecha->nombre_dia);
        $this->assertSame(1, (int) $fecha->es_fin_de_semana);

        $producto = $this->dw()->table('dim_producto')->first();
        $this->assertSame(['Camisa Roja', 'Camisas', 'Hombre'], [$producto->nombre, $producto->categoria, $producto->categoria_principal]);
        $this->assertSame('google', $this->dw()->table('dim_cliente')->value('metodo_registro'));
    }

    public function test_la_carga_es_incremental_y_no_duplica(): void
    {
        $this->venta($this->producto('Camisa Roja', $this->camisas, 80), 150, 80, '2026-10-11 02:00:00');

        $this->artisan('dw:cargar')->assertSuccessful();
        $this->artisan('dw:cargar')->expectsOutputToContain('Ventas: 0')->assertSuccessful();

        $this->assertSame(1, $this->dw()->table('hechos_ventas')->count());
        $this->assertSame(2, $this->dw()->table('etl_cargas')->count());
    }

    public function test_un_pedido_cancelado_despues_se_anula_con_una_fila_negativa(): void
    {
        $pedido = $this->venta($this->producto('Camisa Roja', $this->camisas, 80), 150, 80, '2026-10-11 02:00:00');
        $this->artisan('dw:cargar')->assertSuccessful();

        DB::table('orders')->where('id', $pedido)->update(['status' => 'cancelled', 'updated_at' => '2026-10-13 18:00:00']);
        $this->artisan('dw:cargar')->assertSuccessful();

        $movimientos = $this->dw()->table('hechos_ventas')->orderBy('venta_key')->get();
        $this->assertSame(['venta', 'anulacion'], $movimientos->pluck('tipo_movimiento')->all());
        $this->assertEquals(-150, $movimientos[1]->ingreso);
        $this->assertSame(20261013, (int) $movimientos[1]->fecha_id);
        $this->assertEquals(0, $this->dw()->table('hechos_ventas')->sum('ingreso')); // neto
    }

    public function test_si_cambia_la_categoria_se_agrega_una_version_nueva_del_producto(): void
    {
        $camisa = $this->producto('Camisa Roja', $this->camisas, 80);
        $this->venta($camisa, 150, 80, '2026-10-11 02:00:00');
        $this->artisan('dw:cargar')->assertSuccessful();

        DB::table('products')->where('id', $camisa)->update(['name' => 'Camisa Roja Slim']);
        $this->venta($camisa, 160, 80, '2026-10-12 15:00:00');
        $this->artisan('dw:cargar')->assertSuccessful();

        $versiones = $this->dw()->table('dim_producto')->where('producto_id', $camisa)->orderBy('producto_key')->pluck('nombre')->all();
        $this->assertSame(['Camisa Roja', 'Camisa Roja Slim'], $versiones);

        // La venta vieja sigue apuntando a la versión con el nombre de entonces
        $nombres = $this->dw()->table('hechos_ventas as h')->join('dim_producto as p', 'p.producto_key', '=', 'h.producto_key')
            ->orderBy('h.venta_key')->pluck('p.nombre')->all();
        $this->assertSame(['Camisa Roja', 'Camisa Roja Slim'], $nombres);
    }

    public function test_carga_las_subastas_solo_cuando_su_resultado_es_definitivo(): void
    {
        $postor = DB::table('users')->insertGetId(['name' => 'Eva', 'last_name' => 'R', 'email' => 'eva@test.com', 'password' => 'x', 'email_verified_at' => now()]);
        $chaqueta = $this->producto('Chaqueta', $this->camisas, 100, tipo: 'auction');
        $pedido = $this->venta($chaqueta, 300, 100, '2026-10-12 15:00:00');

        $vendida = DB::table('auctions')->insertGetId(['product_id' => $chaqueta, 'starting_price' => 200, 'current_price' => 300, 'min_increment' => 10,
            'start_date' => '2026-10-05', 'end_date' => '2026-10-10 20:00:00', 'status' => 'finished', 'winner_user_id' => $this->cliente, 'order_id' => $pedido]);
        DB::table('auction_bids')->insert([
            ['auction_id' => $vendida, 'user_id' => $postor, 'amount' => 250],
            ['auction_id' => $vendida, 'user_id' => $this->cliente, 'amount' => 300],
        ]);
        DB::table('auctions')->insert(['product_id' => $this->producto('Botas', $this->botas, 50, tipo: 'auction'), 'starting_price' => 90, 'current_price' => 90,
            'min_increment' => 10, 'start_date' => '2026-10-05', 'end_date' => '2026-10-10 20:00:00', 'status' => 'finished']);
        // Con ganador que aún no confirma: todavía no se carga
        DB::table('auctions')->insert(['product_id' => $this->producto('Gorra', $this->botas, 20, tipo: 'auction'), 'starting_price' => 50, 'current_price' => 70,
            'min_increment' => 10, 'start_date' => '2026-10-05', 'end_date' => '2026-10-15 20:00:00', 'status' => 'finished', 'winner_user_id' => $postor]);

        $this->artisan('dw:cargar')->assertSuccessful();

        $subastas = $this->dw()->table('hechos_subastas')->orderBy('subasta_id')->get();
        $this->assertSame(['vendida', 'sin_pujas'], $subastas->pluck('resultado')->all());
        $this->assertEquals(50, $subastas[0]->incremento_pct);
        $this->assertSame([2, 2], [(int) $subastas[0]->total_pujas, (int) $subastas[0]->postores]);
        $this->assertSame('auction', $this->dw()->table('hechos_ventas')->value('tipo_venta'));
    }

    public function test_el_analisis_resume_lo_que_el_dashboard_no_tiene(): void
    {
        $camisa = $this->producto('Camisa Roja', $this->camisas, 80, '2026-10-01 10:00:00');
        $botas = $this->producto('Botas', $this->botas, 150, '2026-09-01 10:00:00');
        $this->venta($camisa, 150, 80, '2026-10-11 02:00:00');           // sábado 10 oct (GT)
        $this->venta($botas, 200, 150, '2026-10-12 15:00:00');           // lunes 12 oct
        $this->venta($this->producto('Camisa Azul', $this->camisas, 90), 120, 90, '2025-10-20 15:00:00'); // un año antes

        $admin = User::find(DB::table('users')->insertGetId(['name' => 'Ana', 'last_name' => 'A', 'email' => 'ana@test.com', 'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin']));
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/analisis/actualizar')->assertOk()->assertJsonPath('data.ventas', 3);

        $r = $this->getJson('/api/admin/analisis?meses=3')->assertOk()->json('data');

        $this->assertSame(['ingreso' => 350, 'ganancia' => 120, 'unidades' => 2, 'pedidos' => 2, 'ticketPromedio' => 175], $r['resumen']);
        $this->assertSame('ok', $r['ultimaCarga']['estado']);

        $octubre = end($r['tendencia']);
        $this->assertSame(['etiqueta' => 'Oct 26', 'ingreso' => 350, 'ganancia' => 120, 'pedidos' => 2, 'ingresoAnioAnterior' => 120], $octubre);

        $porDia = array_column($r['diasSemana'], 'ingreso', 'dia');
        $this->assertEquals(150, $porDia['Sábado']);
        $this->assertEquals(200, $porDia['Lunes']);
        $this->assertCount(7, $r['diasSemana']);

        $camisas = collect($r['categorias'])->firstWhere('categoria', 'Camisas');
        $this->assertSame(['principal' => 'Hombre', 'categoria' => 'Camisas', 'unidades' => 1, 'ingreso' => 150, 'ganancia' => 70, 'margenPct' => 46.7], $camisas);

        // Luis compró hace un año: en los últimos 3 meses es recurrente
        $this->assertSame(['nuevos' => 0, 'recurrentes' => 1], $r['clientes']);

        $tiempo = collect($r['tiempoVenta'])->keyBy('categoria');
        $this->assertEquals(9, $tiempo['Hombre']['dias']);
        $this->assertEquals(41, $tiempo['Calzado']['dias']);
    }

    public function test_los_hechos_tampoco_se_pueden_modificar(): void
    {
        $this->venta($this->producto('Camisa Roja', $this->camisas, 80), 150, 80, '2026-10-11 02:00:00');
        $this->artisan('dw:cargar')->assertSuccessful();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->dw()->table('hechos_ventas')->update(['ingreso' => 1]);
    }
}
