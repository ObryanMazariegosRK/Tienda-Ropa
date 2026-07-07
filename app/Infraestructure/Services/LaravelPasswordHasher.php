<?php

namespace App\Infraestructure\Services;

use App\Domain\Abstractions\User\IPasswordHasher;
use Illuminate\Support\Facades\Hash;

class LaravelPasswordHasher implements IPasswordHasher{

    //Encriptamos la contraseña
    public function hashPassword(string $password): string{
        return Hash::make($password);
    }

    //Verificamos si la contraseña en texto plano
    //coincide con su version encriptada
    public function verifyPassword(string $password, string $hashedPassword): bool{
        return Hash::check($password, $hashedPassword);
    }




}