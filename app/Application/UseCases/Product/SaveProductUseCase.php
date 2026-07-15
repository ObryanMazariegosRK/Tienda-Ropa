<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IImageStorageService;
use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\SaveProductDTO;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\Product;
use App\Domain\Entities\ProductImage;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Exception;
//Importamos Str para poder generar el slug
use Illuminate\Support\Str; 

class SaveProductUseCase implements ISaveProductUseCase 
{
    public function __construct(
        private IProductRepository $productRepository, 
        private IImageStorageService $imageStorageService
    ) {}

    public function execute(SaveProductDTO $dto): ProductDTO
    {
        //Validaciones
        $saleTypeEnum = ProductSaleType::tryFrom($dto->saleType);
        if(!$saleTypeEnum){
            throw new Exception("El tipo de venta proporcionado no es válido");
        }

        $statusEnum = ProductStatus::tryFrom($dto->status);
        if(!$statusEnum){
            throw new Exception("El estado del producto no es válido");
        }
       
        //Generar Slug
        $slugGenerado = Str::slug($dto->name);

        //Construir la Entidad Principal 
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

        //GUARDAMOS PRIMERO (Para obtener el ID de la base de datos)
        //$savedProduct ya vendra con el ID asignado por MySQL
        $savedProduct = $this->productRepository->save($product);

        //Inicializamos la variable
        $imagesResponse = null; 

        //Procesamos las imagenes basicamente creamos las entidades de ProductImage para guardarlos en la db xd
        if (!empty($dto->images)) {
            $imagePaths = $this->imageStorageService->storeMultiple($dto->images, 'products');

            //linea para depurar
            //dd('Archivos recibidos:', $dto->images, 'Rutas generadas:', $imagePaths);

            $productImageEntities = [];
            foreach ($imagePaths as $path) {
                $productImageEntities[] = new ProductImage(
                    id: null, 
                    //una vez la db ya nos haya dado el id
                    productId: $savedProduct->getId(), 
                    imageUrl: $path
                );
            }

            //Asignamos las imágenes al producto guardado
            $savedProduct->setImages($productImageEntities);

            //Actualizamos el producto para insertar sus imágenes y recibimos las entidades con ID
            $imagenesGuardadas = $this->productRepository->saveImagesForProduct($savedProduct);

            //Armamos el diccionario para que nos retorne el id y la url
            $imagesResponse = [];
            foreach ($imagenesGuardadas as $imgEntity) {
                $imagesResponse[] = [
                    'id' => $imgEntity->getId(),
                    'url' => $imgEntity->getImageUrl()
                ];
            }
        }

        //Retornamos el DTO
        return new ProductDTO(
            id: $savedProduct->getId(),
            categoryId: $savedProduct->getCategoryId(),
            name: $savedProduct->getName(),
            description: $savedProduct->getDescription(),
            slug: $savedProduct->getSlug(),
            price: $savedProduct->getPrice(),
            offerPrice: $savedProduct->getOfferPrice(),
            saleType: $savedProduct->getSaleType()->value, 
            status: $savedProduct->getStatus()->value,
            images: $imagesResponse 
        );

    } 
}