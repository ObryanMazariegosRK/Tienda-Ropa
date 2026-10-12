<?php

namespace App\Domain\Abstractions\User;

use App\Domain\Entities\MailSender;

interface IEmailService{
    //Enviar el código de verificacion al registrar una cuenta nueva
    public function sendVerificationCode(string $toEmail, string $userName, string $code):void;

    //Enviar el código de recuperación de contraseña
    public function sendPasswordRecoveryCode(string $email, string $name, string $code):void;

    //Enviar un correo de prueba con una cuenta concreta (lanza RuntimeException con un mensaje claro si falla)
    public function sendTestEmail(MailSender $sender, string $toEmail):void;
}
