<?php

namespace App\Application\Abstractions\Product;

interface IGetPaginatedProductsUseCase
{
    public function execute(int $page, int $perPage, ?string $status = null, ?int $categoryId = null, ?string $saleType = null, bool $onlyOffers = false): array;
}