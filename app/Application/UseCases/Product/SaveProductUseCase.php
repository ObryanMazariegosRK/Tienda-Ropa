<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IImageStorageService;
use App\Application\Abstractions\Product\ISaveProductUseCase;
use App\Application\DTOs\Product\ProductDTO;
use App\Application\DTOs\Product\AdminProductDTO;
use App\Application\DTOs\Product\SaveProductDTO;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Abstractions\IAuctionRepository;
use App\Domain\Entities\Product;
use App\Domain\Entities\ProductImage;
use App\Domain\Entities\Auction;
use App\Domain\Enum\ProductSaleType;
use App\Domain\Enum\ProductStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DateTimeImmutable;
use Exception;

class SaveProductUseCase implements ISaveProductUseCase
{
    public function __construct(
        private IProductRepository $productRepository,
        private IImageStorageService $imageStorageService,
        private IAuctionRepository $auctionRepository 
    ) {}

    public function execute(SaveProductDTO $dto): AdminProductDTO
    {
        $saleTypeEnum = ProductSaleType::tryFrom($dto->saleType);
        if (!$saleTypeEnum) {
            throw new Exception("El tipo de venta proporcionado no es válido");
        }

        $statusEnum = ProductStatus::tryFrom($dto->status);
        if (!$statusEnum) {
            throw new Exception("El estado del producto no es válido");
        }

        // Validamos los datos de subasta ANTES de tocar la base de datos
        if ($saleTypeEnum === ProductSaleType::AUCTION) {
            if (!$dto->auctionDurationAmount || !$dto->auctionDurationUnit) {
                throw new Exception('Debes indicar la duración de la subasta.');
            }
        }

        $slugGenerado = Str::slug($dto->name);

        $product = new Product(
            id: null,
            categoryId: $dto->categoryId,
            name: $dto->name,
            description: $dto->description,
            slug: $slugGenerado,
            price: $dto->price,
            offerPrice: $dto->offerPrice,
            saleType: $saleTypeEnum,
            status: $statusEnum,
            cost: $dto->cost
        );

        [$savedProduct, $imagesResponse] = DB::transaction(function () use ($dto, $product, $saleTypeEnum) {
            $savedProduct = $this->productRepository->save($product);

            $imagesResponse = null;
            if (!empty($dto->images)) {
                $imagePaths = $this->imageStorageService->storeMultiple($dto->images, 'products');

                $productImageEntities = [];
                foreach ($imagePaths as $path) {
                    $productImageEntities[] = new ProductImage(
                        id: null,
                        productId: $savedProduct->getId(),
                        imageUrl: $path
                    );
                }

                $savedProduct->setImages($productImageEntities);
                $imagenesGuardadas = $this->productRepository->saveImagesForProduct($savedProduct);

                $imagesResponse = [];
                foreach ($imagenesGuardadas as $imgEntity) {
                    $imagesResponse[] = [
                        'id' => $imgEntity->getId(),
                        'url' => $imgEntity->getImageUrl()
                    ];
                }
            }

            // Si el producto es de subasta, creamos la subasta asociada en el mismo paso
            if ($saleTypeEnum === ProductSaleType::AUCTION) {
                $startDate = new DateTimeImmutable();
                $endDate = match ($dto->auctionDurationUnit) {
                    'hours' => $startDate->modify("+{$dto->auctionDurationAmount} hours"),
                    'days'  => $startDate->modify("+{$dto->auctionDurationAmount} days"),
                    'weeks' => $startDate->modify("+{$dto->auctionDurationAmount} weeks"),
                };

                $auction = new Auction(
                    id: null,
                    productId: $savedProduct->getId(),
                    startingPrice: $dto->price,
                    startDate: $startDate,
                    endDate: $endDate,
                    minIncrement: $dto->auctionMinIncrement ?? 10.00
                );

                $this->auctionRepository->create($auction);
            }

            return [$savedProduct, $imagesResponse];
        });

        return new AdminProductDTO(
            id: $savedProduct->getId(),
            categoryId: $savedProduct->getCategoryId(),
            name: $savedProduct->getName(),
            description: $savedProduct->getDescription(),
            slug: $savedProduct->getSlug(),
            price: $savedProduct->getPrice(),
            offerPrice: $savedProduct->getOfferPrice(),
            cost: $savedProduct->getCost(),
            saleType: $savedProduct->getSaleType()->value,
            status: $savedProduct->getStatus()->value,
            images: $imagesResponse
        );
    }
}