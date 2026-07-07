<?php

namespace App\Application\Abstractions\User;
use App\Application\DTOs\User\UserProfileDTO;

interface IGetProfileUseCase{
    public function execute(int $userId): UserProfileDTO;
}