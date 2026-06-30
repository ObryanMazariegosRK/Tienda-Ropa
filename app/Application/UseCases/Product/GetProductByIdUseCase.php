<?php

namespace App\Application\UseCases\Product;
use App\Application\Abstractions\Product\IGetProductByIdUseCase;
use App\Application\DTOs\Product\ProductDTO;
use App\Data\Repositories\ProductRepository;
use App\Domain\Abstractions\IProductRepository;
use Exception;

class GetProductByIdUseCase implements IGetProductByIdUseCase{

    public function __construct(
        private IProductRepository $productRepository
    )
    {}

    public function execute(int $id): ?ProductDTO{

        $product=$this->productRepository-> findById($id);

        if(!$product){
            return null;
        }

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
    }
}