<?php

namespace App\Application\Abstractions\Order;
use App\Application\DTOs\Order\OrderDTO;

interface IGetOrderDetailUseCase {
    public function execute(int $userId, int $orderId): OrderDTO;
}