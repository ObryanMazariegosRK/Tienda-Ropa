<?php

namespace App\Application\Abstractions\Product;

interface IGetProductsByCategoryUseCase
{
    public function execute(int $categoryId): array;
}