<?php

namespace App\Application\UseCases\Address;

use App\Application\Abstractions\Address\ISetDefaultAddressUseCase;
use App\Application\DTOs\Address\AddressDTO;
use App\Domain\Abstractions\IAddressRepository;
use Exception;

class SetDefaultAddressUseCase implements ISetDefaultAddressUseCase
{
    public function __construct(private IAddressRepository $addressRepository) {}

    public function execute(int $userId, int $addressId): AddressDTO
    {
        $address = $this->addressRepository->findByIdAndUser($addressId, $userId);

        if (!$address) {
            throw new Exception('Dirección no encontrada.');
        }

        $this->addressRepository->clearDefaultForUser($userId);
        $address->markAsDefault();
        $updated = $this->addressRepository->update($address);

        return AddressDTO::fromEntity($updated);
    }
}