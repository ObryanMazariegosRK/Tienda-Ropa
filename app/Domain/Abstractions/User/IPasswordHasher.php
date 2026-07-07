<?php

namespace App\Domain\Abstractions\User;

interface IPasswordHasher{
    //Encriptamos la contraseña
    public function hashPassword(string $password): string;
    //Verificamos si una contraseña en texto plano coincide con su version encriptada
    public function verifyPassword(string $password, string $hashedPassword): bool;
}