<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IDeleteProductUseCase;
use App\Domain\Abstractions\IProductRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Domain\Enum\ProductStatus;
use App\Domain\Exceptions\BusinessRuleException;
use Exception;

class DeleteProductUseCase implements IDeleteProductUseCase
{
    public function __construct(
        private IProductRepository $productRepository
    ) {}

    public function execute(int $id): void
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new NotFoundHttpException("El producto con ID {$id} no se encontró.");
        }

        if (in_array($product->getStatus(), [ProductStatus::RESERVED, ProductStatus::SOLD], true)) {
            throw new BusinessRuleException('No puedes eliminar un producto que ya fue vendido o está reservado en una orden.');
        }

        // "Eliminar" = archivar: cambiamos el estado a 'deleted' Y liberamos su slug
        // (le agregamos un sufijo único con su id), para que un producto nuevo pueda
        // volver a usar ese nombre/slug sin chocar con la restricción unique.
        $this->productRepository->archive($id, $product->getSlug());
    }
}