<?php

namespace App\Application\DTOs\User;

readonly class LoginUserDTO{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false
    ){}
}