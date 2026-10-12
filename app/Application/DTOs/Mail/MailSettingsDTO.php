<?php

namespace App\Application\DTOs\Mail;

class MailSettingsDTO
{
    public function __construct(
        /** @var MailSenderDTO[] */
        public readonly array $senders,
        // Cuenta del .env, la que se usa cuando ninguna está en uso
        public readonly ?string $defaultEmail
    ) {}
}
