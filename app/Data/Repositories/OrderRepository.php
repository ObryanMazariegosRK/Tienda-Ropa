<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\IOrderRepository;
use App\Domain\Entities\Order;
use App\Domain\Enum\OrderStatus;
use App\Models\OrderModel;
use DateTimeImmutable;

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

    private const ACTIVE_STATUSES = ['pending_payment', 'confirmed', 'preparing', 'on_route'];
    private const HISTORY_STATUSES = ['delivered', 'cancelled'];

    public function findAll(?string $status = null, ?string $grupo = null): array
    {
        $query = OrderModel::query()->orderByDesc('created_at');

        if ($status) {
            // Un estado específico elegido en el dropdown
            $query->where('status', $status);
        } elseif ($grupo === 'active') {
            $query->whereIn('status', self::ACTIVE_STATUSES);
        } elseif ($grupo === 'history') {
            $query->whereIn('status', self::HISTORY_STATUSES);
        }

        return $query->get()->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    private const REVENUE_STATUSES = ['confirmed', 'preparing', 'on_route', 'delivered'];

    public function updateStatus(int $orderId, string $status): Order
    {
        $model = OrderModel::findOrFail($orderId);

        $data = ['status' => $status];

        // Fijamos confirmed_at UNA sola vez: la primera vez que el pedido entra
        // a cualquiera de los estados donde ya hay dinero real (sin importar si
        // el admin se "saltó" pasos). Si ya tenía fecha, nunca se vuelve a tocar,
        // así retroceda y avance de estado varias veces después.
        if (in_array($status, self::REVENUE_STATUSES, true) && $model->confirmed_at === null) {
            $data['confirmed_at'] = now();
        }

        $model->update($data);
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
            createdAt: new \DateTimeImmutable($model->created_at),
            confirmedAt: $model->confirmed_at ? new DateTimeImmutable($model->confirmed_at) : null
        );
    }
}