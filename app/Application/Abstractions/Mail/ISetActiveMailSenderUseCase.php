<?php

namespace App\Application\Abstractions\Mail;

interface ISetActiveMailSenderUseCase
{
    // $id null = volver a la cuenta del .env
    public function execute(?int $id): void;
}
