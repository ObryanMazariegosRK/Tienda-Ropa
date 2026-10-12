<?php

namespace App\Application\Abstractions\Mail;

use App\Application\DTOs\Mail\MailSenderDTO;

interface ISaveMailSenderUseCase
{
    // $id null = crear. Al editar, $password null = conservar la guardada
    public function execute(?int $id, string $fromName, string $email, ?string $password, string $host, int $port): MailSenderDTO;
}
