<?php
namespace App\Application\Abstractions\Order;

interface IListMyOrdersUseCase {
    public function execute(int $userId): array; // OrderDTO[]
}
