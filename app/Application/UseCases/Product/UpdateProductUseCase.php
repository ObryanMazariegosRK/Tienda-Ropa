<?php

namespace App\Application\UseCases\Product;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\AdminProductDTO;
use App\Application\DTOs\Product\UpdateProductDTO;
use App\Domain\Abstractions\IProductRepository;
use App\Application\Abstractions\Product\IUpdateProductUseCase;
use App\Domain\Entities\Product;
use App\Domain\Entities\ProductImage;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Application\Abstractions\Product\IImageStorageService;
use App\Domain\Exceptions\BusinessRuleException;

class UpdateProductUseCase implements IUpdateProductUseCase{

    public function __construct(
        private IProductRepository $productRepository,
        private IImageStorageService $imageStorageService,
        
    )
    {}

    public function execute(UpdateProductDTO $dto): AdminProductDTO
    {
        $product = $this->productRepository->findById($dto->id);

        if (!$product) {
            throw new NotFoundHttpException("El producto con ID {$dto->id} no existe.");
        }

        // Un producto reservado o vendido ya forma parte del historial de una
        // orden real — no se puede modificar sin romper esa trazabilidad.
        if (in_array($product->getStatus(), [ProductStatus::RESERVED, ProductStatus::SOLD], true)) {
            throw new BusinessRuleException('No puedes editar un producto que ya fue vendido o está reservado en una orden activa.');
        }

        // ==========================================
        //ELIMINAR IMÁGENES VIEJAS
        // ==========================================
        if (!empty($dto->deletedImageIds)) {
            $imagesToDelete = $this->productRepository->findImagesByIds($dto->deletedImageIds);
            
            foreach ($imagesToDelete as $img) {
                //Usamos el servicio de almacenamiento para borrar en vez de Storage directamente
                $this->imageStorageService->delete($img['image_url']); 
            }
            
            // Las borramos de la base de datos
            $this->productRepository->deleteImages($dto->deletedImageIds);
        }

        // ==========================================
        //SUBIR NUEVAS IMÁGENES
        // ==========================================
        $newDomainImages = [];
        if (!empty($dto->newImages)) {
            // 💡 Reutilizamos tu método seguro 'storeMultiple' que ya corregimos antes
            $imagePaths = $this->imageStorageService->storeMultiple($dto->newImages, 'products');

            foreach ($imagePaths as $path) {
                if ($path) { // Evitamos meter paths fallidos (false)
                    // Usamos 'null' o '0' según lo que espere tu entidad para un ID nuevo
                    $newDomainImages[] = new ProductImage(id: null, productId: $dto->id, imageUrl: $path);
                }
            }
        }

        // ==========================================
        //ACTUALIZAR LOS DATOS DEL PRODUCTO
        // ==========================================
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
            status: ProductStatus::from($dto->status),
            cost: $dto->cost 
        );

        if (!empty($newDomainImages)) {
            $productEntity->setImages($newDomainImages);
        }

        // Guardamos los cambios de texto en la BD
        $this->productRepository->update($productEntity);
        
        // Guardamos las nuevas imágenes en la BD
        if (!empty($newDomainImages)) {
            $this->productRepository->saveImagesForProduct($productEntity);
        }

        // ==========================================
        //RETORNAR EL DTO ACTUALIZADO
        // ==========================================
        $freshProduct = $this->productRepository->findById($dto->id);

        return new AdminProductDTO(
            id: $freshProduct->getId(),
            categoryId: $freshProduct->getCategoryId(),
            name: $freshProduct->getName(),
            description: $freshProduct->getDescription(),
            slug: $freshProduct->getSlug(),
            price: $freshProduct->getPrice(),
            offerPrice: $freshProduct->getOfferPrice(),
            cost: $freshProduct->getCost(),
            saleType: $freshProduct->getSaleType()->value,
            status: $freshProduct->getStatus()->value,
            images: array_map(function ($image) {
                return ['id' => $image->getId(), 'url' => $image->getImageUrl()];
            }, $freshProduct->getImages())
        );
    }


}
