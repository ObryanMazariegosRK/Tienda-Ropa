<?php

namespace App\Application\Abstractions\Order;
use App\Application\DTOs\Order\OrderDTO;

interface IUpdateOrderStatusUseCase {
    public function execute(int $orderId, string $newStatus): OrderDTO;
}