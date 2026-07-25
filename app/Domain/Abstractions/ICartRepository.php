<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\CartItem;

interface ICartRepository
{
    public function add(CartItem $item): CartItem;
    public function findByUserAndProduct(int $userId, int $productId): ?CartItem;
    public function findByUserId(int $userId): array;

    /**
     * Igual que findByUserId, pero cada elemento del array trae también
     * el nombre e imagen del producto, para pintarlo en el carrito sin
     * mezclar esos datos dentro de la entidad de dominio CartItem.
     *
     * Cada elemento: ['cartItem' => CartItem, 'productName' => string, 'productImage' => ?string]
     */
    public function findByUserIdWithProductInfo(int $userId): array;

    public function findByIdAndUser(int $cartItemId, int $userId): ?CartItem;
    public function remove(int $cartItemId): bool;
    public function clearByUserId(int $userId): void;
}