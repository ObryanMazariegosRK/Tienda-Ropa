<?php

namespace App\Application\Abstractions\Cart;

use App\Application\DTOs\Cart\CartDTO;

interface IAddToCartUseCase
{
    public function execute(int $userId, int $productId): CartDTO;
}