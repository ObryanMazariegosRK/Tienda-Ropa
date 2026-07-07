<?php

namespace App\Domain\Abstractions\User;
use App\Domain\Entities\User;

interface IUserRepository{
    //Buscar un usuario por su correo
    public function findUserByEmail(string $email):?User;

    //Verificar si existe un usuario registrado con ese correo
    public function existsByEmail(string $email): bool;

    //Guardar un nuevo usuario
    public function addUser(User $user): void;

    //actualizar los datos de un usuario existente
    public function updateUser(User $user): void;

    //Crear un token de acceso para el usuario
    public function createToken(User $user, bool $rememberMe = false): string;

    //Para encontrar una persona por su ID
    public function findUserById(int $id): ?User;
}