<?php

namespace App\Application\Abstractions\Product;

interface IGetPaginatedProductsForAdminUseCase
{
    public function execute(int $page, int $perPage, ?string $status = null, ?int $categoryId = null): array;
}