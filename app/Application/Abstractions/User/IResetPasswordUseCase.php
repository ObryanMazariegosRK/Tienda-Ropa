<?php

namespace App\Application\Abstractions\User;

use App\Application\DTOs\User\ResetPasswordDTO;

interface IResetPasswordUseCase
{
    /**
     * Valida el código de recuperación y actualiza la contraseña del usuario
     *
     * @param ResetPasswordDTO $dto
     * @return void
     * @throws \Exception
     */
    public function execute(ResetPasswordDTO $dto): void;
}