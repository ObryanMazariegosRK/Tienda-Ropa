<?php

namespace App\Application\Abstractions\Order;

interface IListAllOrdersUseCase
{
    public function execute(?string $status = null, ?string $grupo = null): array;
}