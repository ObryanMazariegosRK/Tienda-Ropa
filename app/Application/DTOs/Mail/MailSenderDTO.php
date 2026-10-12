<?php

namespace App\Application\DTOs\Mail;

use App\Domain\Entities\MailSender;

// Nunca incluye la contraseña: el panel solo sabe que existe
class MailSenderDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $fromName,
        public readonly string $email,
        public readonly string $host,
        public readonly int $port,
        public readonly bool $isActive,
        public readonly ?string $lastTestedAt
    ) {}

    public static function fromEntity(MailSender $sender): self
    {
        return new self(
            id: $sender->getId(),
            fromName: $sender->getFromName(),
            email: $sender->getEmail(),
            host: $sender->getHost(),
            port: $sender->getPort(),
            isActive: $sender->isActive(),
            lastTestedAt: $sender->getLastTestedAt()?->format(DATE_ATOM)
        );
    }
}
