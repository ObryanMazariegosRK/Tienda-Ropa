<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\Order;

interface IOrderRepository
{
    public function create(Order $order): Order;
    public function findById(int $id): ?Order;
    public function findByIdAndUser(int $id, int $userId): ?Order;
    public function findByUserId(int $userId): array;
    /** @return array{items: Order[], total: int, lastPage: int, currentPage: int, perPage: int} */
    public function paginateByUserId(int $userId, int $page, int $perPage): array;
    public function findAll(?string $status = null, ?string $grupo = null, ?string $codigo = null): array;
    public function updateStatus(int $orderId, string $status): Order;
}