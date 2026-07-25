<?php
namespace App\Application\Abstractions\Address;
use App\Application\DTOs\Address\AddressDTO;

interface ISetDefaultAddressUseCase {
    public function execute(int $userId, int $addressId): AddressDTO;
}