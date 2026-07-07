<?php

namespace App\Application\DTOs\User;

class ResetPasswordDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $code,
        public readonly string $password
    ) {}
}