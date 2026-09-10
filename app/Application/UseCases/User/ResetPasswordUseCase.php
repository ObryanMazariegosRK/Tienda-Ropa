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

    public function execute(ResetPasswordDTO $dto): string
    {
        $userEntity = $this->userRepository->findUserByEmail($dto->email);

        if (!$userEntity) {
            throw new Exception("El correo electrónico no está registrado.");
        }

        $now = new \DateTimeImmutable();

        if (!$userEntity->isVerificationCodeValid($dto->code, $now)) {
            throw new Exception("El código de recuperación es incorrecto o ha expirado.");
        }

        $hashedPassword = Hash::make($dto->password);
        $userEntity->changePassword($hashedPassword);
        $this->userRepository->updateUser($userEntity);

        // Igual que en verificación de correo: el usuario ya demostró acceso
        // al correo y definió su contraseña nueva, así que lo dejamos logueado.
        return $this->userRepository->createToken($userEntity, true);
    }
}