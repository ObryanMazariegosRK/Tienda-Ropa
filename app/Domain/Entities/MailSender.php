<?php

namespace App\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

// Cuenta de correo (SMTP) desde la que se envían los códigos a los clientes
class MailSender
{
    public function __construct(
        private ?int $id,
        private string $fromName,
        private string $email,
        private ?string $password, // null al editar = conservar la contraseña guardada
        private string $host = 'smtp.gmail.com',
        private int $port = 587,
        private bool $isActive = false,
        private ?DateTimeImmutable $lastTestedAt = null
    ) {
        $this->fromName = trim($fromName);
        $this->email = strtolower(trim($email));
        $this->host = strtolower(trim($host));

        if ($this->fromName === '' || mb_strlen($this->fromName) > 100) {
            throw new InvalidArgumentException('El nombre del remitente es obligatorio (máximo 100 caracteres).');
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo del remitente no es válido.');
        }
        if (!preg_match('/^[a-z0-9.-]+$/', $this->host)) {
            throw new InvalidArgumentException('El servidor SMTP no es válido.');
        }
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('El puerto debe estar entre 1 y 65535.');
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getFromName(): string { return $this->fromName; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): ?string { return $this->password; }
    public function getHost(): string { return $this->host; }
    public function getPort(): int { return $this->port; }
    public function isActive(): bool { return $this->isActive; }
    public function getLastTestedAt(): ?DateTimeImmutable { return $this->lastTestedAt; }

    public function wasTested(): bool
    {
        return $this->lastTestedAt !== null;
    }

    // ¿Cambiaron los datos con los que se conecta al servidor? (si sí, hay que volver a probarla)
    public function connectionChangedFrom(MailSender $anterior): bool
    {
        return $this->password !== null
            || $this->email !== $anterior->getEmail()
            || $this->host !== $anterior->getHost()
            || $this->port !== $anterior->getPort();
    }
}
