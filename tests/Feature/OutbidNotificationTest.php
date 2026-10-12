<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PujaSuperadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OutbidNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $nombre): User
    {
        return User::find(DB::table('users')->insertGetId([
            'name' => $nombre, 'last_name' => 'Prueba', 'email' => strtolower($nombre) . '@test.com',
            'password' => 'x', 'email_verified_at' => now(),
        ]));
    }

    private function pujar(User $user, int $productId, float $monto): void
    {
        Sanctum::actingAs($user);
        $this->postJson("/api/auctions/product/$productId/bid", ['amount' => $monto])->assertOk();
    }

    public function test_solo_avisa_a_quien_acaba_de_perder_el_primer_lugar(): void
    {
        Notification::fake();

        $yo = $this->cliente('Yo');
        $otro = $this->cliente('Otro');
        $tercero = $this->cliente('Tercero');

        $categoryId = DB::table('categories')->insertGetId(['name' => 'Chaquetas', 'description' => 'x', 'slug' => 'chaquetas']);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'name' => 'Chaqueta negra', 'slug' => 'chaqueta-negra',
            'description' => 'x', 'price' => 90, 'sale_type' => 'auction', 'status' => 'available',
        ]);
        DB::table('auctions')->insert([
            'product_id' => $productId, 'starting_price' => 90, 'current_price' => 90, 'min_increment' => 5,
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'status' => 'active',
        ]);

        $this->pujar($yo, $productId, 100);       // nadie iba ganando: no hay aviso
        $this->pujar($otro, $productId, 120);     // me superan: aviso para mí
        $this->pujar($tercero, $productId, 130);  // superan a "otro": aviso para él, no para mí
        $this->pujar($yo, $productId, 140);       // vuelvo a pujar: aviso para "tercero"
        $this->pujar($otro, $productId, 150);     // me superan otra vez: segundo aviso para mí

        Notification::assertSentToTimes($yo, PujaSuperadaNotification::class, 2);
        Notification::assertSentToTimes($otro, PujaSuperadaNotification::class, 1);
        Notification::assertSentToTimes($tercero, PujaSuperadaNotification::class, 1);
    }
}
