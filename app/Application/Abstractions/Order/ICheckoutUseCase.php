<?php
namespace App\Application\Abstractions\Order;
use App\Application\DTOs\Order\CheckoutResultDTO;

interface ICheckoutUseCase {
    public function execute(int $userId, int $addressId): CheckoutResultDTO;
}