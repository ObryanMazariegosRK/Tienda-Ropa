<?php

namespace App\Application\UseCases\Address;

use App\Application\Abstractions\Address\IListAddressesUseCase;
use App\Application\DTOs\Address\AddressDTO;
use App\Domain\Abstractions\IAddressRepository;

class ListAddressesUseCase implements IListAddressesUseCase
{
    public function __construct(private IAddressRepository $addressRepository) {}

    public function execute(int $userId): array
    {
        $addresses = $this->addressRepository->findByUserId($userId);
        return array_map(fn($a) => AddressDTO::fromEntity($a), $addresses);
    }
}