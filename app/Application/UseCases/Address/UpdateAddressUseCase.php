<?php

namespace App\Application\UseCases\Address;

use App\Application\Abstractions\Address\IUpdateAddressUseCase;
use App\Application\DTOs\Address\AddressDTO;
use App\Domain\Abstractions\IAddressRepository;
use Exception;

class UpdateAddressUseCase implements IUpdateAddressUseCase
{
    public function __construct(private IAddressRepository $addressRepository) {}

    public function execute(int $userId, int $addressId, string $label, string $addressLine): AddressDTO
    {
        $address = $this->addressRepository->findByIdAndUser($addressId, $userId);

        if (!$address) {
            throw new Exception('Dirección no encontrada.');
        }

        $address->update($label, $addressLine);
        $updated = $this->addressRepository->update($address);

        return AddressDTO::fromEntity($updated);
    }
}