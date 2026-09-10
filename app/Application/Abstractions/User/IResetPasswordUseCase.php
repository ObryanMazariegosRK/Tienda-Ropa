<?php

namespace App\Application\Abstractions\User;

use App\Application\DTOs\User\ResetPasswordDTO;

interface IResetPasswordUseCase
{
    /**
     * Valida el código de recuperación y actualiza la contraseña del usuario
     *
     * @param ResetPasswordDTO $dto
     * @throws \Exception
     */
    public function execute(ResetPasswordDTO $dto): string;
}