<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IGetAllProductsUseCase;
use App\Domain\Abstractions\IProductRepository;
use App\Application\DTOs\Product\ProductDTO;

class GetAllProductsUseCase implements IGetAllProductsUseCase
{
    public function __construct(
        private IProductRepository $productRepository
    ) {}

    public function execute(): array
    {
        
        $products = $this->productRepository->findAll();

        //Mapeamos cada entidad Product a un ProductDTO
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