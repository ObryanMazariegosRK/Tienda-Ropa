<?php

namespace App\Application\Abstractions\Cart;

use App\Application\DTOs\Cart\CartDTO;

interface IRemoveFromCartUseCase
{
    public function execute(int $userId, int $cartItemId): CartDTO;
}