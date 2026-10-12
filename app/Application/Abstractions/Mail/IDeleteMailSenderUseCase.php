<?php

namespace App\Application\Abstractions\Mail;

interface IDeleteMailSenderUseCase
{
    public function execute(int $id): void;
}
