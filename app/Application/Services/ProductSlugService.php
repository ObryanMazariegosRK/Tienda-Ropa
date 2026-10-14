<?php
namespace App\Application\Services;

use App\Domain\Abstractions\IProductRepository;
use Illuminate\Support\Str;

// El slug de un producto es único en la base de datos, pero el nombre solo es único
// dentro de su categoría: "Camisa Roja" en Hombre y en Niños → camisa-roja y camisa-roja-2
class ProductSlugService
{
    public function __construct(private IProductRepository $productRepository) {}

    public function unico(string $nombre, ?int $exceptoProductoId = null): string
    {
        $base = Str::slug($nombre) ?: 'producto';
        $slug = $base;
        $numero = 2;

        while ($this->productRepository->slugExists($slug, $exceptoProductoId)) {
            $slug = "{$base}-{$numero}";
            $numero++;
        }

        return $slug;
    }
}
