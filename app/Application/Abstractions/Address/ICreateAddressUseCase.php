<?php
namespace App\Application\Abstractions\Address;
use App\Application\DTOs\Address\AddressDTO;

interface ICreateAddressUseCase {
    public function execute(int $userId, string $label, string $addressLine, bool $isDefault): AddressDTO;
}