<?php

namespace App\Application\UseCases\Product;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\UpdateProductDTO;
use App\Domain\Abstractions\IProductRepository;
use App\Application\Abstractions\Product\IUpdateProductUseCase;
use App\Domain\Entities\Product;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateProductUseCase implements IUpdateProductUseCase{

    public function __construct(
        private IProductRepository $productRepository
    )
    {}

    public function execute(UpdateProductDTO $dto): ProductDTO{

        $product= $this->productRepository->findById($dto->id);

        if(!$product){
            throw new NotFoundHttpException("El producto con ID {$dto->id} 
            no existe en la base de datos.");
        }



        $slugGenerado = Str::slug($dto->name);
        
        $productEntity = new Product(
            id: $dto->id, 
            categoryId: $dto->categoryId, 
            name: $dto->name,
            description: $dto->description, 
            slug: $slugGenerado, 
            price: $dto->price,
            offerPrice: $dto->offerPrice,
            saleType: ProductSaleType::from($dto->saleType), 
            status: ProductStatus::from($dto->status) 
        );

        $this->productRepository->update($productEntity);
        
        return new ProductDTO(
            id: $productEntity->getId(),
            categoryId: $productEntity->getCategoryId(),
            name: $productEntity->getName(),
            description: $productEntity->getDescription(),
            slug: $productEntity->getSlug(),
            price: $productEntity->getPrice(),
            offerPrice: $productEntity->getOfferPrice(),
            saleType: $productEntity->getSaleType()->value, 
            status: $productEntity->getStatus()->value 
        );

    }


}
