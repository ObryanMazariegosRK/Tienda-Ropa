<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function categoria(string $nombre, ?int $padreId = null, bool $activa = true): int
    {
        return DB::table('categories')->insertGetId([
            'name' => $nombre, 'description' => $nombre, 'slug' => str()->slug($nombre),
            'parent_category_id' => $padreId, 'is_active' => $activa,
        ]);
    }

    private function producto(string $nombre, int $categoriaId): void
    {
        DB::table('products')->insert([
            'category_id' => $categoriaId, 'name' => $nombre, 'slug' => str()->slug($nombre),
            'description' => $nombre, 'price' => 100, 'sale_type' => 'direct', 'status' => 'available',
            'created_at' => now(),
        ]);
    }

    public function test_filtrar_por_categoria_padre_incluye_sus_subcategorias(): void
    {
        $mujer = $this->categoria('Mujer');
        $vestidos = $this->categoria('Vestidos', $mujer);
        $hombre = $this->categoria('Hombre');

        $this->producto('Blusa', $mujer);
        $this->producto('Vestido rojo', $vestidos);
        $this->producto('Camisa', $hombre);

        $nombres = collect($this->getJson("/api/products?category_id=$mujer")->assertOk()->json('data'))
            ->pluck('name')->sort()->values()->all();
        $this->assertSame(['Blusa', 'Vestido rojo'], $nombres);

        $nombres = collect($this->getJson("/api/products?category_id=$vestidos")->json('data'))->pluck('name')->all();
        $this->assertSame(['Vestido rojo'], $nombres);

        $this->assertCount(3, $this->getJson('/api/products')->json('data'));
    }

    public function test_per_page_tiene_un_limite(): void
    {
        $this->getJson('/api/products?per_page=100000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 60);
    }

    public function test_la_tienda_oculta_productos_de_categorias_desactivadas(): void
    {
        $hombre = $this->categoria('Hombre');
        $camisas = $this->categoria('Camisas', $hombre);
        $temporada = $this->categoria('Temporada', null, activa: false);
        $vestidos = $this->categoria('Vestidos', $this->categoria('Mujer', null, activa: false)); // hija activa, principal inactiva
        $gorras = $this->categoria('Gorras', $hombre, activa: false);

        $this->producto('Camisa azul', $camisas);
        $this->producto('Suéter navideño', $temporada);
        $this->producto('Vestido rojo', $vestidos);
        $this->producto('Gorra negra', $gorras);

        $nombres = collect($this->getJson('/api/products')->assertOk()->json('data'))->pluck('name')->all();
        $this->assertSame(['Camisa azul'], $nombres);

        // Filtrar por "Hombre" tampoco trae los de su subcategoría inactiva
        $nombres = collect($this->getJson("/api/products?category_id=$hombre")->json('data'))->pluck('name')->all();
        $this->assertSame(['Camisa azul'], $nombres);
    }

    public function test_el_admin_sigue_viendo_los_productos_de_categorias_desactivadas(): void
    {
        $temporada = $this->categoria('Temporada', null, activa: false);
        $this->producto('Suéter navideño', $temporada);

        \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::find(DB::table('users')->insertGetId([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin',
        ])));

        $nombres = collect($this->getJson('/api/admin/products')->assertOk()->json('data'))->pluck('name')->all();
        $this->assertSame(['Suéter navideño'], $nombres);
    }
}
