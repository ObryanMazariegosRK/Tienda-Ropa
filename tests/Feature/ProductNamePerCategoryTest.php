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
