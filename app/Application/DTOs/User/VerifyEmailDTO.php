<?php

namespace App\Application\DTOs\User;

class VerifyEmailDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $code
    ) {}
}