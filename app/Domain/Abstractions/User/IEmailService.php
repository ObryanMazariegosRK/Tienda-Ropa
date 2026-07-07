<?php

namespace App\Domain\Abstractions\User;

interface IEmailService{
    //Enviar el código de verificacion al registrar una cuenta nueva
    public function sendVerificationCode(string $toEmail, string $userName, string $code):void;

    //Enviar el código de recuperación de contraseña
    public function sendPasswordRecoveryCode(string $email, string $name, string $code):void;
}