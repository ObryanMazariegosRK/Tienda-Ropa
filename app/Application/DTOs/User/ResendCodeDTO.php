<?php
namespace App\Application\DTOs\User;

class ResendCodeDTO{
    public function __construct(
        public readonly string $email
    ){}

}