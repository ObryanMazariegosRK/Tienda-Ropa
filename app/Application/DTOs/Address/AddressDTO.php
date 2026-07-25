<?php

namespace App\Application\DTOs\Address;

class AddressDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly string $addressLine,
        public readonly bool $isDefault
    ) {}

    public static function fromEntity(\App\Domain\Entities\Address $address): self
    {
        return new self(
            id: $address->getId(),
            label: $address->getLabel(),
            addressLine: $address->getAddressLine(),
            isDefault: $address->isDefault()
        );
    }
}