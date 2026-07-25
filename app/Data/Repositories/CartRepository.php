<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\ICartRepository;
use App\Domain\Entities\CartItem;
use App\Models\CartItemModel;

class CartRepository implements ICartRepository
{
    public function add(CartItem $item): CartItem
    {
        $model = CartItemModel::create([
            'user_id' => $item->getUserId(),
            'product_id' => $item->getProductId(),
            'price_snapshot' => $item->getPriceSnapshot(),
        ]);

        return $this->mapToDomain($model);
    }

    public function findByUserAndProduct(int $userId, int $productId): ?CartItem
    {
        $model = CartItemModel::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        return $model ? $this->mapToDomain($model) : null;
    }

    public function findByUserId(int $userId): array
    {
        $models = CartItemModel::where('user_id', $userId)
            ->with('product.images')
            ->get();

        return $models->map(fn($m) => $this->mapToDomain($m))->toArray();
    }

    public function findByIdAndUser(int $cartItemId, int $userId): ?CartItem
    {
        $model = CartItemModel::where('id', $cartItemId)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->mapToDomain($model) : null;
    }

    public function remove(int $cartItemId): bool
    {
        return (bool) CartItemModel::destroy($cartItemId);
    }

    private function mapToDomain(CartItemModel $model): CartItem
    {
        return new CartItem(
            id: $model->id,
            userId: $model->user_id,
            productId: $model->product_id,
            priceSnapshot: (float) $model->price_snapshot,
            createdAt: $model->created_at?->toDateTimeString()
        );
    }

    public function findByUserIdWithProductInfo(int $userId): array
    {
        $models = CartItemModel::where('user_id', $userId)
            ->with('product.images')
            ->get();

        return $models->map(function ($model) {
            $product = $model->product;

            $imagenPrincipal = ($product && $product->images->isNotEmpty())
                ? $product->images->first()->image_url
                : null;

            return [
                'cartItem' => $this->mapToDomain($model),
                'productName' => $product?->name ?? 'Producto no disponible',
                'productImage' => $imagenPrincipal,
                'originalPrice' => $product ? (float) $product->price : null,
            ];
        })->toArray();
    }

    public function clearByUserId(int $userId): void
    {
        CartItemModel::where('user_id', $userId)->delete();
    }
}