<?php

namespace App\Application\UseCases\Address;

use App\Application\Abstractions\Address\IDeleteAddressUseCase;
use App\Domain\Abstractions\IAddressRepository;
use Exception;

class DeleteAddressUseCase implements IDeleteAddressUseCase
{
    public function __construct(private IAddressRepository $addressRepository) {}

    public function execute(int $userId, int $addressId): void
    {
        $address = $this->addressRepository->findByIdAndUser($addressId, $userId);

        if (!$address) {
            throw new Exception('Dirección no encontrada.');
        }

        $this->addressRepository->delete($addressId);

        // Si la que se borró era la predeterminada y quedan otras, promovemos la primera
        if ($address->isDefault()) {
            $restantes = $this->addressRepository->findByUserId($userId);
            if (count($restantes) > 0) {
                $restantes[0]->markAsDefault();
                $this->addressRepository->update($restantes[0]);
            }
        }
    }
}