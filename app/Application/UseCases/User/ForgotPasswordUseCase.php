<?php

namespace App\Application\UseCases\User;

use App\Application\Abstractions\User\IForgotPasswordUseCase;


use App\Application\DTOs\User\ForgotPasswordDTO;
use App\Domain\Abstractions\User\IUserRepository; 
use App\Domain\Abstractions\User\IEmailService;     
use Exception;

class ForgotPasswordUseCase implements IForgotPasswordUseCase
{
    public function __construct(
        private IUserRepository $userRepository,
        private IEmailService $emailService
    ) {}

    public function execute(ForgotPasswordDTO $dto): void
    {
        //Buscamos al usuario por su correo electrónico
        $userEntity = $this->userRepository->findUserByEmail($dto->email);


        //Por seguridad, detenemos la ejecución solo con un return simulando que todo salió bien xd
        if (!$userEntity) {
            return; 
        }

        //Generamos el código de 6 dígitos y el tiempo de expiración 
        $resetCode = (string) rand(100000, 999999);
        $expiresAt = new \DateTimeImmutable('+15 minutes');

        //Actualizamos la entidad del usuario con el código de recuperación
        $userEntity->assignNewVerificationCode($resetCode, $expiresAt);

        //Guardamos los cambios
        $this->userRepository->updateUser($userEntity);

        //Enviamos el correo
        $this->emailService->sendPasswordRecoveryCode(
            $userEntity->getEmail(),
            $userEntity->getFullName(),
            $resetCode
        );
        
    }
}