<?php

namespace App\Domain\Abstractions\User;
use App\Domain\Entities\User;
interface IAuthUseCase{

    //Iniciar sesion
    public function login(string $email, string $password):? bool;

    //Registrar un nuevo usuario
    public function register(User $user, string $plainPassword): void;

    //Generamos el código de recuperacaion
    public function forgotPassword(string $email):void;
    
    //Validamos el código enviado y actualizamos la contraseña
    public function resetPassword(string $email, string $code, string $newPassword): void;

}