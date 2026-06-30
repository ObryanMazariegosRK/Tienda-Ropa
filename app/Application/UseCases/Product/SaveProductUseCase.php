<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\SaveProductDTO;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Product;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Exception;
//Importamos Str para poder generar el slug
use Illuminate\Support\Str; 

class SaveProductUseCase implements ISaveProductUseCase 
{
    public function __construct(
        private IProductRepository $productRepository 
    ) {}

    public function execute(SaveProductDTO $dto): ProductDTO
    {
        //Validamos y convertimos el string del DTO al Enum del domino
        $saleTypeEnum = ProductSaleType::tryFrom($dto->saleType);

        if(!$saleTypeEnum){
            throw new Exception("El tipo de venta proporcionado no es valido");
        }

        $statusEnum= ProductStatus::tryFrom($dto->status);

        if(!$statusEnum){
            throw new Exception("El estado del producto no es valido");
        }
       
        //Creamos el slug internamente usando el nombre
        $slugGenerado = Str::slug($dto->name);

        //Construimos la entidad usando parámetros nombrados
        $product = new Product(
            id: null,
            categoryId: $dto->categoryId,
            name: $dto->name,
            description: $dto->description,
            slug: $slugGenerado,
            price: $dto->price,
            offerPrice: $dto->offerPrice,
            saleType: $saleTypeEnum,
            status: $statusEnum
        ); 

        //Pasamos el objeto al repositorio
        $savedProduct = $this->productRepository->save($product);

        //Volvemos a convertir el objeto en un DTO y lo retornamos
        return new ProductDTO(
            id: $savedProduct->getId(),
            categoryId: $savedProduct->getCategoryId(),
            name: $savedProduct->getName(),
            description: $savedProduct->getDescription(),
            slug: $savedProduct->getSlug(),
            price: $savedProduct->getPrice(),
            offerPrice: $savedProduct->getOfferPrice(),
            saleType: $savedProduct->getSaleType()->value, 
            status: $savedProduct->getStatus()->value    
        );
    } 
}