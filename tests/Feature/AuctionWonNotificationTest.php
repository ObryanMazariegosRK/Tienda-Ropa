<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SubastaGanadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuctionWonNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_aviso_de_subasta_ganada_incluye_el_nombre_del_producto(): void
    {
        Notification::fake();

        $ganador = User::find(DB::table('users')->insertGetId([
            'name' => 'Ganador', 'last_name' => 'Prueba', 'email' => 'ganador@test.com',
            'password' => 'x', 'email_verified_at' => now(),
        ]));
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Chaquetas', 'description' => 'x', 'slug' => 'chaquetas']);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'name' => 'Chaqueta negra', 'slug' => 'chaqueta-negra',
            'description' => 'x', 'price' => 90, 'sale_type' => 'auction', 'status' => 'available',
        ]);
        DB::table('auctions')->insert([
            'product_id' => $productId, 'starting_price' => 90, 'current_price' => 120, 'min_increment' => 5,
            'start_date' => now()->subDays(2), 'end_date' => now()->subMinute(), 'status' => 'active',
            'current_winner_user_id' => $ganador->id,
        ]);

        $this->artisan('auctions:close-expired')->assertSuccessful();

        Notification::assertSentTo($ganador, SubastaGanadaNotification::class, function ($notification) use ($ganador) {
            $body = $notification->toWebPush($ganador, $notification)->toArray()['body'];
            return str_contains($body, 'Chaqueta negra');
        });
    }
}
