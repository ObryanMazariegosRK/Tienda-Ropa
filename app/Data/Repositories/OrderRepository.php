<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Entities\Order;
use App\Domain\Enum\OrderStatus;
use App\Models\OrderModel;

class OrderRepository implements IOrderRepository
{
    public function create(Order $order): Order
    {
        $model = OrderModel::create([
            'user_id' => $order->getUserId(),
            'address_id' => $order->getAddressId(),
            'shipping_address' => $order->getShippingAddress(),
            'total' => $order->getTotal(),
            'status' => $order->getStatus()->value,
        ]);

        return $this->mapToDomain($model);
    }

    public function findById(int $id): ?Order
    {
        $model = OrderModel::find($id);
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByIdAndUser(int $id, int $userId): ?Order
    {
        $model = OrderModel::where('id', $id)->where('user_id', $userId)->first();
        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByUserId(int $userId): array
    {
        $models = OrderModel::where('user_id', $userId)->orderByDesc('created_at')->get();
        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    public function findAll(?string $status = null): array
    {
        $query = OrderModel::query()->orderByDesc('created_at');
        if ($status) {
            $query->where('status', $status);
        }
        return $query->get()->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    public function updateStatus(int $orderId, string $status): Order
    {
        $model = OrderModel::findOrFail($orderId);
        $model->update(['status' => $status]);
        return $this->mapToDomain($model->fresh());
    }

    private function mapToDomain(OrderModel $model): Order
    {
        return new Order(
            id: $model->id,
            userId: $model->user_id,
            addressId: $model->address_id,
            shippingAddress: $model->shipping_address,
            total: (float) $model->total,
            status: OrderStatus::from($model->status),
            createdAt: new \DateTimeImmutable($model->created_at)
        );
    }
}