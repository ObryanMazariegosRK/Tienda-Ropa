<?php

namespace App\Application\Abstractions\Product;

interface IGetAllProductsUseCase
{
    public function execute(): array;
}