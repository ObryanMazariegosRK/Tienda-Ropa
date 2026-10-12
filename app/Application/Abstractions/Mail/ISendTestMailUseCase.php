<?php

namespace App\Application\Abstractions\Mail;

interface ISendTestMailUseCase
{
    public function execute(int $senderId, string $toEmail): void;
}
