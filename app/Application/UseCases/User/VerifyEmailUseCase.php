<?php

namespace App\Application\UseCases\User;

use App\Application\Abstractions\User\IVerifyEmailUseCase;
use App\Application\DTOs\User\VerifyEmailDTO;
use App\Domain\Abstractions\User\IUserRepository;
use Exception;

class VerifyEmailUseCase implements IVerifyEmailUseCase
{
    public function __construct(
        private IUserRepository $userRepository
    ) {}

    public function execute(VerifyEmailDTO $dto): string
    {
        $userEntity = $this->userRepository->findUserByEmail($dto->email);

        if (!$userEntity) {
            throw new Exception("No se encontró ningún usuario con el correo proporcionado.");
        }

        if ($userEntity->isEmailVerified()) {
            throw new Exception("Este correo electrónico ya se encuentra verificado.");
        }

        $now = new \DateTimeImmutable();

        if (!$userEntity->isVerificationCodeValid($dto->code, $now)) {
            throw new Exception("El código de verificación es incorrecto o ha expirado.");
        }

        $userEntity->markEmailAsVerified($now);
        $this->userRepository->updateUser($userEntity);

        // Generamos el token de sesión de una vez, para que el frontend
        // pueda iniciar sesión automáticamente sin pedir credenciales de nuevo.
        // rememberMe: true → sesión de 30 días, como un "primer inicio de sesión" cómodo.
        return $this->userRepository->createToken($userEntity, true);
    }
}