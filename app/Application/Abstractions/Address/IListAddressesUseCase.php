<?php
namespace App\Application\Abstractions\Address;

interface IListAddressesUseCase {
    public function execute(int $userId): array; // AddressDTO[]
}