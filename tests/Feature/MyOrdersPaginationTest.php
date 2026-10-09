<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyOrdersPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $email): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Cliente', 'last_name' => 'Prueba', 'email' => $email,
            'password' => 'x', 'email_verified_at' => now(),
        ]);
    }

    private function pedidos(int $userId, int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            DB::table('orders')->insert([
                'user_id' => $userId, 'shipping_address' => "Dirección $i", 'total' => $i,
                'status' => 'pending_payment', 'created_at' => now()->subDays($cantidad - $i),
            ]);
        }
    }

    public function test_devuelve_los_pedidos_por_lotes_del_mas_reciente_al_mas_antiguo(): void
    {
        $yo = $this->cliente('yo@test.com');
        $otro = $this->cliente('otro@test.com');
        $this->pedidos($yo, 12);
        $this->pedidos($otro, 5);
        Sanctum::actingAs(User::find($yo));

        $pagina1 = $this->getJson('/api/orders/mine?page=1&per_page=10')->assertOk();
        $pagina1->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.total', 12); // el más reciente primero

        $this->getJson('/api/orders/mine?page=2&per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.total', 1); // el más antiguo al final
    }

    public function test_el_tamano_del_lote_tiene_un_limite(): void
    {
        Sanctum::actingAs(User::find($this->cliente('yo@test.com')));

        $this->getJson('/api/orders/mine?per_page=5000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 30);
    }
}
