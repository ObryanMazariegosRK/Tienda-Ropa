<?php

namespace App\Application\UseCases\Cart;

use App\Application\Abstractions\Cart\IGetCartUseCase;
use App\Application\DTOs\Cart\CartDTO;
use App\Application\DTOs\Cart\CartItemDTO;
use App\Domain\Abstractions\ICartRepository;

class GetCartUseCase implements IGetCartUseCase
{
    public function __construct(
        private ICartRepository $cartRepository
    ) {}

    public function execute(int $userId): CartDTO
    {
        $rows = $this->cartRepository->findByUserIdWithProductInfo($userId);

        $itemDTOs = array_map(function ($row) {
            $cartItem = $row['cartItem'];
            $precioGuardado = $cartItem->getPriceSnapshot();
            $precioOriginal = $row['originalPrice'];

            // Solo mostramos "originalPrice" si de verdad hay diferencia (fue oferta)
            $huboOferta = $precioOriginal !== null && $precioOriginal > $precioGuardado;

            return new CartItemDTO(
                id: $cartItem->getId(),
                productId: $cartItem->getProductId(),
                productName: $row['productName'],
                productImage: $row['productImage'],
                price: $precioGuardado,
                originalPrice: $huboOferta ? $precioOriginal : null
            );
        }, $rows);

        $total = array_sum(array_map(fn($i) => $i->price, $itemDTOs));

        return new CartDTO(
            items: $itemDTOs,
            total: $total,
            itemCount: count($itemDTOs)
        );
    }
}