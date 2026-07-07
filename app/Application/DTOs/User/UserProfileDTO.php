<?php

namespace App\Application\DTOs\User;

readonly class UserProfileDTO{

    public function __construct(
        public int $id,
        public string $name,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $role
    ){}

    /**
     * Método que recibe la Entidad de Dominio y mapea campo por campo
     * la estructura del DTO omitiendo la contraseña
     */

    public static function fromEntity(\App\Domain\Entities\User $user): self
    {
        return new self(
            id: $user->getId(),
            name: $user->getName(),
            lastName: $user->getLastName(),
            email: $user->getEmail(),
            phone: $user->getPhone(),
            role: $user->getRole()->value 
        );
    }

}