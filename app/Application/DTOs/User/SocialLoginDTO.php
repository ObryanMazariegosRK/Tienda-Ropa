<?php

namespace App\Application\DTOs\User;

class SocialLoginDTO
{

    public function __construct(
        public readonly string $provider, //google | facebook
        public readonly string $providerId,
        public readonly string $email,
        public readonly string $name,
        public readonly ?string $lastName=null,
    ){}


}