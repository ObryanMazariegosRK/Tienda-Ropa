<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IOrderDetailRepository;
use App\Domain\Entities\OrderDetail;
use App\Models\OrderDetailModel;

class OrderDetailRepository implements IOrderDetailRepository
{
    public function createMany(array $orderDetails): void
    {
        foreach ($orderDetails as $detail) {
            OrderDetailModel::create([
                'order_id' => $detail->getOrderId(),
                'product_id' => $detail->getProductId(),
                'quantity' => $detail->getQuantity(),
                'unit_price' => $detail->getUnitPrice(),
            ]);
        }
    }

    public function findByOrderIdWithProductInfo(int $orderId): array
    {
        $models = OrderDetailModel::where('order_id', $orderId)
            ->with('product.images')
            ->get();

        return $models->map(function ($model) {
            $product = $model->product;

            $imagenPrincipal = ($product && $product->images->isNotEmpty())
                ? $product->images->first()->image_url
                : null;

            return [
                'detail' => new OrderDetail(
                    id: $model->id,
                    orderId: $model->order_id,
                    productId: $model->product_id,
                    unitPrice: (float) $model->unit_price,
                    quantity: $model->quantity
                ),
                'productName' => $product?->name ?? 'Producto no disponible',
                'productImage' => $imagenPrincipal,
            ];
        })->toArray();
    }
}