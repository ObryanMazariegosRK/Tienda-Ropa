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
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Application\Abstractions\Product\IImageStorageService;
use App\Application\Services\ProductSlugService;
use App\Domain\Exceptions\BusinessRuleException;

class UpdateProductUseCase implements IUpdateProductUseCase{

    public function __construct(
        private IProductRepository $productRepository,
        private IImageStorageService $imageStorageService,
        private ProductSlugService $slugService
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
        //ACTUALIZAR LOS DATOS DEL PRODUCTO
        // ==========================================
        $slugGenerado = $this->slugService->unico($dto->name, $dto->id);
        
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

        // ==========================================
        //IMÁGENES: validar el total y subir las nuevas
        // ==========================================
        // Solo se pueden quitar imágenes de este mismo producto
        $imagesToDelete = !empty($dto->deletedImageIds)
            ? array_values(array_filter(
                $this->productRepository->findImagesByIds($dto->deletedImageIds),
                fn($img) => (int) $img['product_id'] === $dto->id
            ))
            : [];

        $totalImagenes = count($product->getImages()) - count($imagesToDelete) + count($dto->newImages ?? []);
        if ($totalImagenes > Product::MAX_IMAGES) {
            throw new BusinessRuleException('Un producto admite máximo ' . Product::MAX_IMAGES . ' imágenes. Quita algunas antes de agregar más.');
        }

        // Se guardan ya optimizadas; si algo falla más abajo se borran
        $imagePaths = !empty($dto->newImages) ? $this->imageStorageService->storeMultiple($dto->newImages, 'products') : [];
        $newDomainImages = array_map(
            fn($path) => new ProductImage(id: null, productId: $dto->id, imageUrl: $path),
            $imagePaths
        );

        if (!empty($newDomainImages)) {
            $productEntity->setImages($newDomainImages);
        }

        try {
            DB::transaction(function () use ($productEntity, $newDomainImages, $imagesToDelete) {
                $this->productRepository->update($productEntity);

                if (!empty($imagesToDelete)) {
                    $this->productRepository->deleteImages(array_column($imagesToDelete, 'id'));
                }
                if (!empty($newDomainImages)) {
                    $this->productRepository->saveImagesForProduct($productEntity);
                }
            });
        } catch (\Throwable $e) {
            $this->imageStorageService->deleteMany($imagePaths);
            throw $e;
        }

        // Los archivos viejos se borran solo cuando la base de datos ya quedó actualizada
        $this->imageStorageService->deleteMany(array_column($imagesToDelete, 'image_url'));

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
