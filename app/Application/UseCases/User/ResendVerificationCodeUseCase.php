<?php

namespace App\Application\UseCases\User;

use App\Application\Abstractions\User\IResendVerificationCodeUseCase;
use App\Application\DTOs\User\ResendCodeDTO;
use App\Domain\Abstractions\User\IUserRepository;
use App\Domain\Abstractions\User\IEmailService;
use Exception;

class ResendVerificationCodeUseCase implements IResendVerificationCodeUseCase
{
    public function __construct(
        private IUserRepository $userRepository,
        private IEmailService $emailService
    ) {}

    public function execute(ResendCodeDTO $dto): void
    {
        //Buscamos al usuario
        $userEntity = $this->userRepository->findUserByEmail($dto->email);

        if (!$userEntity) {
            throw new Exception("No se encontró ningún usuario con el correo proporcionado");
        }

        //Si ya está verificado, rebotamos la acción
        if ($userEntity->isEmailVerified()) {
            throw new Exception("Este correo electrónico ya se encuentra verificado.");
        }

        //Generamos el nuevo código y nueva fecha de expiración
        $newCode = (string) rand(100000, 999999);
        $newExpiresAt = new \DateTimeImmutable('+15 minutes');

        //Actualizamos los valores dentro de la Entidad
        $userEntity->assignNewVerificationCode($newCode, $newExpiresAt);

        //El repositorio se encarga de hacer el UPDATE en la fila de MySQL
        $this->userRepository->updateUser($userEntity);

        //Volvemos a disparar el servicio de correo con el nuevo código
        $this->emailService->sendVerificationCode(
            $userEntity->getEmail(),
            $userEntity->getFullName(),
            $newCode
        );
    }
}