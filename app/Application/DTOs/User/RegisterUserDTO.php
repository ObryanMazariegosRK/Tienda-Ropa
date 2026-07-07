<?php

namespace App\Application\DTOs\User;

readonly class RegisterUserDTO{
    public function __construct(
        public string $name,
        public string $lastName,
        public string $email,
        public string $password,
        public string $phone 
    ){}
}