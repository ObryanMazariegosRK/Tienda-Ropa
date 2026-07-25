<?php

namespace App\Application\UseCases\Cart;

use App\Application\Abstractions\Cart\IAddToCartUseCase;
use App\Application\Abstractions\Cart\IGetCartUseCase;
use App\Application\DTOs\Cart\CartDTO;
use App\Domain\Abstractions\ICartRepository;
use App\Domain\Abstractions\IProductRepository;
use App\Domain\Entities\CartItem;
use Exception;

class AddToCartUseCase implements IAddToCartUseCase
{
    public function __construct(
        private ICartRepository $cartRepository,
        private IProductRepository $productRepository,
        private IGetCartUseCase $getCartUseCase
    ) {}

    public function execute(int $userId, int $productId): CartDTO
    {
        $product = $this->productRepository->findById($productId);

        if (!$product) {
            throw new Exception('El producto no existe.');
        }

        if ($product->getStatus()->value !== 'available') {
            throw new Exception('Este producto ya no está disponible.');
        }

        $yaExiste = $this->cartRepository->findByUserAndProduct($userId, $productId);
        if ($yaExiste) {
            throw new Exception('Este producto ya está en tu carrito.');
        }

        // Precio efectivo: si tiene oferta, esa es la que se cobra
        $precioFinal = $product->getOfferPrice() ?? $product->getPrice();

        $this->cartRepository->add(new CartItem(
            id: null,
            userId: $userId,
            productId: $productId,
            priceSnapshot: $precioFinal
        ));

        return $this->getCartUseCase->execute($userId);
    }
}