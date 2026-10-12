<?php

namespace App\Application\UseCases\Mail;

use App\Application\Abstractions\Mail\ISaveMailSenderUseCase;
use App\Application\DTOs\Mail\MailSenderDTO;
use App\Domain\Abstractions\IMailSenderRepository;
use App\Domain\Entities\MailSender;
use InvalidArgumentException;

class SaveMailSenderUseCase implements ISaveMailSenderUseCase
{
    public function __construct(private IMailSenderRepository $repository) {}

    public function execute(?int $id, string $fromName, string $email, ?string $password, string $host, int $port): MailSenderDTO
    {
        // Gmail muestra la contraseña de aplicación en grupos ("abcd efgh ijkl mnop") separados con
        // espacios duros (no los normales), así que quitamos cualquier tipo de espacio o carácter invisible
        $password = $password !== null ? preg_replace('/[\s\p{Z}\x{200B}-\x{200D}\x{FEFF}]+/u', '', $password) : null;
        if ($password === '') {
            $password = null;
        }

        $anterior = $id !== null ? $this->repository->findById($id) : null;
        if ($id !== null && !$anterior) {
            throw new InvalidArgumentException('La cuenta de correo no existe.');
        }
        if ($anterior === null && $password === null) {
            throw new InvalidArgumentException('Escribe la contraseña de aplicación de la cuenta.');
        }

        $sender = new MailSender(
            id: $id,
            fromName: $fromName,
            email: $email,
            password: $password,
            host: $host,
            port: $port,
            isActive: $anterior?->isActive() ?? false,
            lastTestedAt: $anterior?->getLastTestedAt()
        );

        $duplicado = $this->repository->findByEmail($sender->getEmail());
        if ($duplicado && $duplicado->getId() !== $id) {
            throw new InvalidArgumentException('Esa cuenta de correo ya está registrada.');
        }

        $guardado = $anterior
            // Si cambió cómo se conecta, la prueba anterior ya no vale
            ? $this->repository->update($sender, resetTested: $sender->connectionChangedFrom($anterior))
            : $this->repository->create($sender);

        return MailSenderDTO::fromEntity($guardado);
    }
}
