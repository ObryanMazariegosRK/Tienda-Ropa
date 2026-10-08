<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuctionReassignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_nuevo_ganador_recibe_un_plazo_completo_al_reasignar(): void
    {
        [$primero, $segundo, $tercero] = array_map(fn ($i) => (object) ['id' => DB::table('users')->insertGetId([
            'name' => "Cliente $i", 'last_name' => 'Prueba', 'email' => "cliente$i@test.com",
            'password' => 'x', 'email_verified_at' => now(),
        ])], [1, 2, 3]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Vestidos', 'description' => 'Vestidos', 'slug' => 'vestidos',
        ]);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'name' => 'Vestido rojo', 'slug' => 'vestido-rojo',
            'description' => 'Vestido', 'price' => 100, 'sale_type' => 'auction', 'status' => 'reserved',
        ]);

        // Subasta que el primer ganador dejó vencer hace 4 días (el límite es 3)
        $auctionId = DB::table('auctions')->insertGetId([
            'product_id' => $productId, 'starting_price' => 50, 'current_price' => 100,
            'min_increment' => 10, 'start_date' => now()->subDays(6), 'end_date' => now()->subDays(4),
            'status' => 'finished', 'current_winner_user_id' => $primero->id,
            'winner_user_id' => $primero->id, 'won_at' => now()->subDays(4),
        ]);
        foreach ([[$primero, 100], [$segundo, 90], [$tercero, 80]] as [$user, $amount]) {
            DB::table('auction_bids')->insert([
                'auction_id' => $auctionId, 'user_id' => $user->id, 'amount' => $amount,
                'created_at' => now()->subDays(5),
            ]);
        }

        $this->artisan('auctions:expire-unclaimed-wins')->assertSuccessful();
        $this->assertSame($segundo->id, DB::table('auctions')->find($auctionId)->winner_user_id);

        // Una segunda corrida no debe saltarse al segundo ganador: su plazo apenas empieza
        $this->artisan('auctions:expire-unclaimed-wins')->assertSuccessful();
        $this->assertSame($segundo->id, DB::table('auctions')->find($auctionId)->winner_user_id);
    }
}
