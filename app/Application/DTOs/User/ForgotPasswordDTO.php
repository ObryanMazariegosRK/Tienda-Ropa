<?php

namespace App\Application\DTOs\User;

class ForgotPasswordDTO
{
    public function __construct(
        public readonly string $email
    ) {}
}