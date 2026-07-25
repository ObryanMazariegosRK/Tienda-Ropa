<?php
namespace App\Application\Abstractions\Address;
use App\Application\DTOs\Address\AddressDTO;

interface IUpdateAddressUseCase {
    public function execute(int $userId, int $addressId, string $label, string $addressLine): AddressDTO;
}