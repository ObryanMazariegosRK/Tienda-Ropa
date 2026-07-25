<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\OrderDetail;

interface IOrderDetailRepository
{
    public function createMany(array $orderDetails): void; // OrderDetail[]

    /**
     * Devuelve los detalles de una orden enriquecidos con datos del producto.
     * Cada elemento: ['detail' => OrderDetail, 'productName' => string, 'productImage' => ?string]
     */
    public function findByOrderIdWithProductInfo(int $orderId): array;
}