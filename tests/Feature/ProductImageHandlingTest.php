<?php

namespace Tests\Feature;

use App\Data\Repositories\ProductRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductImageHandlingTest extends TestCase
{
    use RefreshDatabase;

    private int $categoriaId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Sanctum::actingAs(User::find(DB::table('users')->insertGetId([
            'name' => 'Admin', 'last_name' => 'Tienda', 'email' => 'admin@test.com',
            'password' => 'x', 'email_verified_at' => now(), 'role' => 'admin',
        ])));
        $this->categoriaId = DB::table('categories')->insertGetId(['name' => 'Camisas', 'description' => 'x', 'slug' => 'camisas']);
    }

    private function crearProducto(array $imagenes)
    {
        return $this->post('/api/products', [
            'categoryId' => $this->categoriaId, 'name' => 'Camisa de prueba', 'description' => 'Camisa',
            'price' => 150, 'cost' => 80, 'saleType' => 'direct', 'status' => 'available',
            'images' => $imagenes,
        ], ['Accept' => 'application/json']);
    }

    public function test_las_fotos_se_reducen_y_comprimen_al_subirlas(): void
    {
        $this->crearProducto([UploadedFile::fake()->image('foto.jpg', 2400, 1800)])->assertCreated();

        $path = DB::table('product_images')->value('image_url');
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.webp', $path);

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame([1600, 1200], [$ancho, $alto]); // conserva la proporción
    }

    public function test_una_foto_pequena_no_se_agranda(): void
    {
        $this->crearProducto([UploadedFile::fake()->image('foto.png', 800, 1000)])->assertCreated();

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path(DB::table('product_images')->value('image_url')));
        $this->assertSame([800, 1000], [$ancho, $alto]);
    }

    public function test_un_producto_admite_maximo_8_fotos(): void
    {
        $fotos = array_map(fn($i) => UploadedFile::fake()->image("foto$i.jpg", 100, 100), range(1, Product::MAX_IMAGES + 1));

        $this->crearProducto($fotos)->assertStatus(422)
            ->assertJsonValidationErrors(['images' => 'Un producto admite máximo 8 imágenes.']);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_si_falla_el_guardado_no_quedan_fotos_sueltas_en_el_disco(): void
    {
        $this->app->bind(IProductRepository::class, fn() => new class extends ProductRepository {
            public function saveImagesForProduct(Product $product): array
            {
                throw new \RuntimeException('Fallo de la base de datos');
            }
        });

        $this->crearProducto([UploadedFile::fake()->image('foto.jpg', 500, 500)])->assertStatus(400);

        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_al_editar_no_se_pueden_pasar_de_8_fotos_ni_borrar_las_de_otro_producto(): void
    {
        $this->crearProducto(array_map(fn($i) => UploadedFile::fake()->image("foto$i.jpg", 100, 100), range(1, 7)))->assertCreated();
        $productoId = DB::table('products')->value('id');

        $otroProducto = DB::table('products')->insertGetId([
            'category_id' => $this->categoriaId, 'name' => 'Otro', 'slug' => 'otro', 'description' => 'x',
            'price' => 100, 'cost' => 50, 'sale_type' => 'direct', 'status' => 'available',
        ]);
        $fotoAjena = DB::table('product_images')->insertGetId(['product_id' => $otroProducto, 'image_url' => 'products/ajena.webp']);

        $datos = [
            '_method' => 'PUT', 'categoryId' => $this->categoriaId, 'name' => 'Camisa de prueba', 'description' => 'Camisa',
            'price' => 150, 'cost' => 80, 'saleType' => 'direct', 'status' => 'available',
        ];

        // 7 + 2 = 9: se rechaza, aunque "quite" una foto que es de otro producto
        $this->post("/api/products/$productoId", $datos + [
            'new_images' => [UploadedFile::fake()->image('a.jpg', 100, 100), UploadedFile::fake()->image('b.jpg', 100, 100)],
            'deleted_images' => [$fotoAjena],
        ], ['Accept' => 'application/json'])->assertStatus(422)
            ->assertJsonFragment(['Un producto admite máximo 8 imágenes. Quita algunas antes de agregar más.']);

        $this->assertTrue(DB::table('product_images')->where('id', $fotoAjena)->exists());
        $this->assertCount(7, Storage::disk('public')->allFiles());
    }

    public function test_el_comando_de_huerfanos_solo_borra_con_delete(): void
    {
        Storage::disk('public')->put('products/usada.webp', 'x');
        Storage::disk('public')->put('products/huerfana.webp', 'x');
        Storage::disk('public')->put('banners/huerfano.webp', 'x');
        touch(Storage::disk('public')->path('products/huerfana.webp'), now()->subDay()->getTimestamp());
        touch(Storage::disk('public')->path('banners/huerfano.webp'), now()->subDay()->getTimestamp());
        // Recién subida (puede ser de una subida en curso): no se toca
        Storage::disk('public')->put('products/recien.webp', 'x');

        $producto = DB::table('products')->insertGetId([
            'category_id' => $this->categoriaId, 'name' => 'Otro', 'slug' => 'otro', 'description' => 'x',
            'price' => 100, 'cost' => 50, 'sale_type' => 'direct', 'status' => 'available',
        ]);
        DB::table('product_images')->insert(['product_id' => $producto, 'image_url' => 'products/usada.webp']);

        $this->artisan('images:clean-orphans')->expectsOutputToContain('2 archivos sin usar')->assertSuccessful();
        $this->assertCount(4, Storage::disk('public')->allFiles());

        $this->artisan('images:clean-orphans --delete --force')->assertSuccessful();
        $this->assertEqualsCanonicalizing(
            ['products/usada.webp', 'products/recien.webp'],
            Storage::disk('public')->allFiles()
        );
    }

    public function test_el_comando_optimiza_las_fotos_viejas_y_respalda_los_originales(): void
    {
        Storage::fake('local');
        $original = UploadedFile::fake()->image('vieja.png', 2000, 2000);
        Storage::disk('public')->putFileAs('products', $original, 'vieja.png');

        $producto = DB::table('products')->insertGetId([
            'category_id' => $this->categoriaId, 'name' => 'Otro', 'slug' => 'otro', 'description' => 'x',
            'price' => 100, 'cost' => 50, 'sale_type' => 'direct', 'status' => 'available',
        ]);
        DB::table('product_images')->insert(['product_id' => $producto, 'image_url' => 'products/vieja.png']);

        $this->artisan('images:optimize')->assertSuccessful();

        $nueva = DB::table('product_images')->value('image_url');
        $this->assertStringEndsWith('.webp', $nueva);
        $this->assertFalse(Storage::disk('public')->exists('products/vieja.png'));
        $this->assertSame([1600, 1600], array_slice(getimagesize(Storage::disk('public')->path($nueva)), 0, 2));
        $this->assertCount(1, Storage::disk('local')->allFiles()); // el respaldo

        // Correrlo otra vez no vuelve a comprimir (no le quita más calidad)
        $this->artisan('images:optimize')->expectsOutputToContain('0 fotos por optimizar')->assertSuccessful();
    }
}
