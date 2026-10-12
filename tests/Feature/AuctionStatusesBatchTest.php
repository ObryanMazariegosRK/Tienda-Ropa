<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuctionStatusesBatchTest extends TestCase
{
    use RefreshDatabase;

    private function subasta(string $nombre, float $precio): int
    {
        $categoria = DB::table('categories')->value('id')
            ?? DB::table('categories')->insertGetId(['name' => 'Ropa', 'description' => 'x', 'slug' => 'ropa']);
        $productoId = DB::table('products')->insertGetId([
            'category_id' => $categoria, 'name' => $nombre, 'slug' => str()->slug($nombre), 'description' => 'x',
            'price' => $precio, 'cost' => 10, 'sale_type' => 'auction', 'status' => 'available',
        ]);
        DB::table('auctions')->insert([
            'product_id' => $productoId, 'starting_price' => $precio, 'current_price' => $precio, 'min_increment' => 5,
            'start_date' => now(), 'end_date' => now()->addHour(), 'status' => 'active', 'created_at' => now(),
        ]);
        return $productoId;
    }

    public function test_devuelve_el_estado_de_varias_subastas_en_una_peticion(): void
    {
        $chaqueta = $this->subasta('Chaqueta', 100);
        $botas = $this->subasta('Botas', 250);

        $respuesta = $this->getJson("/api/auctions/status?product_ids=$chaqueta,$botas,999999")->assertOk();

        $respuesta->assertJsonPath("data.$chaqueta.currentPrice", 100)
            ->assertJsonPath("data.$botas.currentPrice", 250)
            ->assertJsonMissingPath('data.999999'); // sin subasta: no aparece
        $this->assertEqualsWithDelta(3600, $respuesta->json("data.$botas.secondsRemaining"), 5);
    }

    public function test_limita_la_cantidad_de_productos(): void
    {
        $this->getJson('/api/auctions/status?product_ids=')->assertStatus(422);
        $this->getJson('/api/auctions/status?product_ids=' . implode(',', range(1, 61)))->assertStatus(422);
    }
}
