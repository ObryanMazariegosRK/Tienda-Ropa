<?php

namespace App\Application\Abstractions\Mail;

use App\Application\DTOs\Mail\MailSettingsDTO;

interface IListMailSendersUseCase
{
    public function execute(): MailSettingsDTO;
}
