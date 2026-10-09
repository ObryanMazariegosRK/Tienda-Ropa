<?php
namespace App\Application\Abstractions\Order;

interface IListMyOrdersUseCase {
    // ['data' => OrderDTO[], 'meta' => [current_page, last_page, per_page, total]]
    public function execute(int $userId, int $page = 1, int $perPage = 10): array;
}
