<?php

namespace App\Application\UseCases\User;

use App\Application\DTOs\User\ResetPasswordDTO;
use App\Application\Abstractions\User\IResetPasswordUseCase;
use App\Domain\Abstractions\User\IUserRepository;
use Illuminate\Support\Facades\Hash;
use Exception;

class ResetPasswordUseCase implements IResetPasswordUseCase
{
    public function __construct(
        private IUserRepository $userRepository
    ) {}

    public function execute(ResetPasswordDTO $dto): void
    {
        //Buscamos al usuario por correo
        $userEntity = $this->userRepository->findUserByEmail($dto->email);

        if (!$userEntity) {
            throw new Exception("El correo electrónico no está registrado.");
        }

        //Creamos la fecha actual para compararla
        $now = new \DateTimeImmutable();

        //Validamos si el código coincide y si aún no ha expirado
        if (!$userEntity->isVerificationCodeValid($dto->code, $now)) {
            throw new Exception("El código de recuperación es incorrecto o ha expirado.");
        }

        //Encriptamos la nueva contraseña antes de guardarla 
        $hashedPassword = Hash::make($dto->password);

        //Utilizamos un método de la entidad para actualizar los datos internos.
        $userEntity->changePassword($hashedPassword);

        //Persistimos los cambios en la base de datos
        $this->userRepository->updateUser($userEntity);
    }
}