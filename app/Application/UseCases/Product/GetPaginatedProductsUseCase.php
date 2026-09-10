<?php

namespace App\Application\UseCases\Product;

use App\Application\Abstractions\Product\IGetPaginatedProductsUseCase;
use App\Domain\Abstractions\IProductRepository;
use App\Application\DTOs\Product\ProductDTO;

class GetPaginatedProductsUseCase implements IGetPaginatedProductsUseCase
{
    public function __construct(
        private IProductRepository $productRepository
    ) {}

    public function execute(int $page, int $perPage, ?string $status = null, ?int $categoryId = null, ?string $saleType = null, bool $onlyOffers = false): array
    {
        $result = $this->productRepository->paginate($page, $perPage, $status, $categoryId, $saleType, $onlyOffers);

        $items = array_map(function ($product) {
            return new ProductDTO(
                id: $product->getId(),
                categoryId: $product->getCategoryId(),
                name: $product->getName(),
                description: $product->getDescription(),
                slug: $product->getSlug(),
                price: $product->getPrice(),
                offerPrice: $product->getOfferPrice(),
                saleType: $product->getSaleType()->value,
                status: $product->getStatus()->value,
                images: array_map(function ($image) {
                    return [
                        'id' => $image->getId(),
                        'url' => $image->getImageUrl()
                    ];
                }, $product->getImages())
            );
        }, $result['items']);

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $result['currentPage'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ];
    }
}