<?php

namespace App\Application\UseCases\Cart;

use App\Application\Abstractions\Cart\IRemoveFromCartUseCase;
use App\Application\Abstractions\Cart\IGetCartUseCase;
use App\Application\DTOs\Cart\CartDTO;
use App\Domain\Abstractions\ICartRepository;
use Exception;

class RemovedFromCartUseCase implements IRemoveFromCartUseCase
{
    public function __construct(
        private ICartRepository $cartRepository,
        private IGetCartUseCase $getCartUseCase
    ) {}

    public function execute(int $userId, int $cartItemId): CartDTO
    {
        // Verificamos que el ítem exista Y le pertenezca a ESTE usuario,
        // para que nadie pueda borrar carritos ajenos adivinando IDs.
        $item = $this->cartRepository->findByIdAndUser($cartItemId, $userId);

        if (!$item) {
            throw new Exception('El producto no existe en tu carrito.');
        }

        $this->cartRepository->remove($cartItemId);

        return $this->getCartUseCase->execute($userId);
    }
}