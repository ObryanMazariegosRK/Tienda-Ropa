<?php

namespace App\Application\DTOs\Order;

class CheckoutResultDTO
{
    public function __construct(
        public readonly OrderDTO $order,
        public readonly string $whatsappUrl
    ) {}
}