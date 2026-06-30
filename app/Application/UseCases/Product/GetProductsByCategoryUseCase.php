<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IGetProductsByCategoryUseCase;
use App\Domain\Abstractions\IProductRepository;
use App\Application\DTOs\Product\ProductDTO;

class GetProductsByCategoryUseCase implements IGetProductsByCategoryUseCase
{
    public function __construct(
        private IProductRepository $productRepository
    ) {}

    public function execute(int $categoryId): array
    {
        //Buscamos los productos filtrados por la categoría
        $products = $this->productRepository->findByCategoryId($categoryId);

        //Mapeamos las Entidades a DTOs 
        return array_map(function ($product) {
            return new ProductDTO(
                id: $product->getId(),
                categoryId: $product->getCategoryId(),
                name: $product->getName(),
                description: $product->getDescription(),
                slug: $product->getSlug(),
                price: $product->getPrice(),
                offerPrice: $product->getOfferPrice(),
                saleType: $product->getSaleType()->value,
                status: $product->getStatus()->value
            );
        }, $products);
    }
}