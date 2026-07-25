<?php

namespace App\Application\Abstractions\Cart;

use App\Application\DTOs\Cart\CartDTO;

interface IGetCartUseCase
{
    public function execute(int $userId): CartDTO;
}