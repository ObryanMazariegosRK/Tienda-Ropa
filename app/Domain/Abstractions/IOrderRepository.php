<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\Order;

interface IOrderRepository
{
    public function create(Order $order): Order;
    public function findById(int $id): ?Order;
    public function findByIdAndUser(int $id, int $userId): ?Order;
    public function findByUserId(int $userId): array;
    public function findAll(?string $status = null): array;
    public function updateStatus(int $orderId, string $status): Order;
}