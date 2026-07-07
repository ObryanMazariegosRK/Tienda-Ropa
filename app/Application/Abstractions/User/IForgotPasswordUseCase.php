<?php

namespace App\Application\Abstractions\User;

use App\Application\DTOs\User\ForgotPasswordDTO;

interface IForgotPasswordUseCase
{
    /**
     * Procesa la solicitud de recuperación de contraseña generando un código temporal
     *
     * @param ForgotPasswordDTO $dto
     * @return void
     * @throws \Exception
     */
    public function execute(ForgotPasswordDTO $dto): void;
}