<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardReportsTest extends TestCase
{
    use RefreshDatabase;

    private int $clienteId;
    private int $categoriaId;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        Sanctum::actingAs(User::find(DB::table('users')->insertGetId([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin',
        ])));
        $this->clienteId = DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => 'cliente@test.com',
            'password' => bcrypt('Secreta123!'), 'email_verified_at' => now(), 'phone' => '55123456',
        ]);
        $this->categoriaId = DB::table('categories')->insertGetId(['name' => 'Camisas', 'description' => 'x', 'slug' => 'camisas']);
    }

    private function producto(float $precio, float $costo, string $estado = 'sold'): int
    {
        static $n = 0;
        $n++;
        return DB::table('products')->insertGetId([
            'category_id' => $this->categoriaId, 'name' => "Camisa $n", 'slug' => "camisa-$n",
            'description' => 'x', 'price' => $precio, 'cost' => $costo, 'sale_type' => 'direct', 'status' => $estado,
        ]);
    }

    // Pedido confirmado en una fecha/hora UTC exacta, con una sola línea
    private function venta(string $confirmadoUtc, float $precio, ?float $costoEnLaVenta, int $productoId): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $this->clienteId, 'shipping_address' => 'Zona 1', 'total' => $precio,
            'status' => 'confirmed', 'confirmed_at' => $confirmadoUtc, 'created_at' => $confirmadoUtc,
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'product_id' => $productoId, 'quantity' => 1,
            'unit_price' => $precio, 'unit_cost' => $costoEnLaVenta,
        ]);
    }

    public function test_una_venta_de_la_noche_cuenta_en_su_dia_de_guatemala(): void
    {
        // 5 oct 02:00 UTC = 4 oct 8:00 PM en Guatemala
        $this->venta('2026-10-05 02:00:00', 300, 100, $this->producto(300, 100));

        $dia4 = $this->getJson('/api/dashboard/revenue?start=2026-10-04&end=2026-10-04')->assertOk();
        $dia4->assertJsonPath('data.totalRevenue', 300)
            ->assertJsonPath('data.dailyBreakdown.0.date', '2026-10-04')
            ->assertJsonPath('data.dailyBreakdown.0.revenue', 300);

        $this->getJson('/api/dashboard/revenue?start=2026-10-05&end=2026-10-05')
            ->assertJsonPath('data.totalRevenue', 0);
    }

    public function test_el_periodo_anterior_se_calcula_en_dias_de_guatemala(): void
    {
        // 4 oct 8:00 PM en Guatemala: pertenece al periodo anterior de "5 y 6 de octubre"
        $this->venta('2026-10-05 02:00:00', 300, 100, $this->producto(300, 100));

        $this->getJson('/api/dashboard/revenue?start=2026-10-05&end=2026-10-06')
            ->assertJsonPath('data.totalRevenue', 0)
            ->assertJsonPath('data.previousPeriodRevenue', 300);
    }

    public function test_la_ganancia_usa_el_costo_del_momento_de_la_venta(): void
    {
        $productoId = $this->producto(200, 120);
        $this->venta('2026-10-04 15:00:00', 200, 120, $productoId);

        // Después de la venta, el admin cambia el costo del producto
        DB::table('products')->where('id', $productoId)->update(['cost' => 190]);

        $this->getJson('/api/dashboard/revenue?start=2026-10-04&end=2026-10-04')
            ->assertJsonPath('data.totalProfit', 80);
    }

    public function test_nuevos_hoy_usa_el_dia_de_guatemala(): void
    {
        // Pedido de las 9:00 AM del 10 oct en Guatemala (15:00 UTC)
        DB::table('orders')->insert([
            'user_id' => $this->clienteId, 'shipping_address' => 'Zona 1', 'total' => 100,
            'status' => 'pending_payment', 'created_at' => '2026-10-10 15:00:00', 'updated_at' => '2026-10-10 15:00:00',
        ]);

        // Son las 8:00 PM del 10 oct en Guatemala (en UTC ya es 11 oct)
        $this->travelTo(Carbon::parse('2026-10-10 20:00:00', 'America/Guatemala'));

        $this->getJson('/api/dashboard/order-stats')->assertOk()->assertJsonPath('data.newToday', 1);
    }

    public function test_el_checkout_guarda_el_costo_del_producto_en_el_detalle(): void
    {
        $productoId = $this->producto(250, 140, 'available');
        $addressId = DB::table('addresses')->insertGetId([
            'user_id' => $this->clienteId, 'label' => 'Casa', 'address_line' => 'Zona 1', 'is_default' => true,
        ]);
        DB::table('cart_items')->insert(['user_id' => $this->clienteId, 'product_id' => $productoId, 'price_snapshot' => 250]);

        Sanctum::actingAs(User::find($this->clienteId));
        $this->postJson('/api/orders/checkout', ['addressId' => $addressId])->assertCreated();

        $this->assertEquals(140, DB::table('order_items')->where('product_id', $productoId)->value('unit_cost'));
    }
}
