<?php

namespace App\Application\Abstractions\User;

use App\Application\DTOs\User\RegisterUserDTO;


interface IRegisterUserUseCase{

    public function execute(RegisterUserDTO $dto): void;
}