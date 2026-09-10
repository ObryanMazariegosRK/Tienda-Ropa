<?php

namespace App\Application\Abstractions\Product;

use App\Application\DTOs\Product\SaveProductDTO;
use App\Application\DTOs\Product\AdminProductDTO;

interface ISaveProductUseCase
{
    public function execute(SaveProductDTO $dto): AdminProductDTO;
}