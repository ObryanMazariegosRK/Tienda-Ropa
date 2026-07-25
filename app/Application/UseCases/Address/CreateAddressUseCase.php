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
        // Si esta se marca como predeterminada, quitamos el flag de cualquier otra
        if ($isDefault) {
            $this->addressRepository->clearDefaultForUser($userId);
        }

        // Si es la primera dirección del usuario, la forzamos como predeterminada
        $tieneOtras = count($this->addressRepository->findByUserId($userId)) > 0;
        if (!$tieneOtras) {
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