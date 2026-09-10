<?php

namespace App\Application\Abstractions\User;

use App\Application\DTOs\User\VerifyEmailDTO;

interface IVerifyEmailUseCase
{
    /**
     * Verificacion del correo
     * * @param VerifyEmailDTO $dto
     * @throws \Exception Si el código es inválido o expiró
     */
    public function execute(VerifyEmailDTO $dto): string;
}