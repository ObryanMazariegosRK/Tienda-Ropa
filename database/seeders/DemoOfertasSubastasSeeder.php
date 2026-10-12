<?php

namespace Database\Seeders;

use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use App\Models\ProductModel;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para ver la paginación de Rebajas y Subastas.
 *
 * Completa hasta OBJETIVO productos disponibles de cada tipo (no suma de más si
 * ya hay suficientes), así que se puede ejecutar varias veces sin duplicar:
 *
 *   docker compose exec app php artisan db:seed --class=DemoOfertasSubastasSeeder
 *
 * Con 24 productos por página, 60 = 3 páginas en cada sección.
 */
class DemoOfertasSubastasSeeder extends Seeder
{
    private const OBJETIVO = 60;

    public function run(): void
    {
        // Misma condición que usa la tienda para mostrar un producto en Rebajas
        $ofertas = ProductModel::where('status', ProductStatus::AVAILABLE->value)
            ->where('sale_type', ProductSaleType::DIRECT->value)
            ->whereNotNull('offer_price')
            ->whereColumn('offer_price', '<', 'price')
            ->count();

        $subastas = ProductModel::where('status', ProductStatus::AVAILABLE->value)
            ->where('sale_type', ProductSaleType::AUCTION->value)
            ->count();

        $faltanOfertas = max(0, self::OBJETIVO - $ofertas);
        $faltanSubastas = max(0, self::OBJETIVO - $subastas);

        if ($faltanOfertas > 0) {
            ProductModel::factory()->count($faltanOfertas)->onOffer()->create();
        }
        // auction() también crea la subasta activa de cada producto
        if ($faltanSubastas > 0) {
            ProductModel::factory()->count($faltanSubastas)->auction()->create();
        }

        $this->command?->info("Ofertas: {$ofertas} + {$faltanOfertas} nuevas. Subastas: {$subastas} + {$faltanSubastas} nuevas.");
    }
}
