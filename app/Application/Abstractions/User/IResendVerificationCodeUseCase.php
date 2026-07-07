<?php

namespace App\Application\Abstractions\User;

use App\Application\DTOs\User\ResendCodeDTO;

interface IResendVerificationCodeUseCase
{
    public function execute(ResendCodeDTO $dto): void;
}