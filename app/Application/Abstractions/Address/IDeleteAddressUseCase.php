<?php
namespace App\Application\Abstractions\Address;

interface IDeleteAddressUseCase {
    public function execute(int $userId, int $addressId): void;
}