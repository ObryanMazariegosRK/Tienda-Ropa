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

    public function execute(VerifyEmailDTO $dto): void
    {
        //Buscamos al usuario en la base de datos a través de su email
        $userEntity = $this->userRepository->findUserByEmail($dto->email);

        //Si el usuario no existe
        if (!$userEntity) {
            throw new Exception("No se encontró ningún usuario con el correo proporcionado.");
        }

        //Si el usuario ya está verificado, no hace falta volver a hacerlo
        if ($userEntity->isEmailVerified()) {
            throw new Exception("Este correo electrónico ya se encuentra verificado.");
        }

        //Obtenemos el tiempo actual (para comparar contra la expiración del código)
        $now = new \DateTimeImmutable();

        // Le preguntamos a la propia Entidad si el código que envió el cliente es válido
        if (!$userEntity->isVerificationCodeValid($dto->code, $now)) {
            throw new Exception("El código de verificación es incorrecto o ha expirado.");
        }

        //La entidad procesa su verificación interna, asigna la fecha y limpia el código
        $userEntity->markEmailAsVerified($now);

        //Guardamos los cambios permanentemente en la base de datos
        $this->userRepository->updateUser($userEntity);
    }
}