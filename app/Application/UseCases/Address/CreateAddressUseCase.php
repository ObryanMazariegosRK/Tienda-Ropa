<?php

namespace App\Application\UseCases\Address;

use App\Application\Abstractions\Address\ICreateAddressUseCase;
use App\Application\DTOs\Address\AddressDTO;
use App\Domain\Abstractions\IAddressRepository;
use App\Domain\Entities\Address;

class CreateAddressUseCase implements ICreateAddressUseCase
{
    public function __construct(private IAddressRepository $addressRepository) {}

    public function execute(int $userId, string $label, string $addressLine, bool $isDefault): AddressDTO
    {
        $existentes = $this->addressRepository->findByUserId($userId);

        if (count($existentes) >= 5) {
            throw new \InvalidArgumentException('Ya tienes el máximo de 5 direcciones guardadas. Elimina una para poder agregar otra.');
        }

        if ($isDefault) {
            $this->addressRepository->clearDefaultForUser($userId);
        }

        if (count($existentes) === 0) {
            $isDefault = true;
        }

        $address = new Address(
            id: null,
            userId: $userId,
            label: $label,
            addressLine: $addressLine,
            isDefault: $isDefault
        );

        $created = $this->addressRepository->create($address);

        return AddressDTO::fromEntity($created);
    }
}