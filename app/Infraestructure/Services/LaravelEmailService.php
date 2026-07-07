<?php

namespace App\Infraestructure\Services;

use App\Domain\Abstractions\User\IEmailService;
use App\Mail\PasswordRecoveryMail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;

class LaravelEmailService implements IEmailService{

     
    public function sendVerificationCode(string $email, string $userName, string $code): void{
        //Mail::to es como el cartero por asi decirlo, en este caso le decimos
        //a donde enviar el sobre 
        Mail::to($email)->send(new VerificationCodeMail($userName, $code));
    }

    
    public function sendPasswordRecoveryCode(string $email, string $name, string $code): void{
        Mail::to($email)->send(new PasswordRecoveryMail($name, $code));
    }

    

}