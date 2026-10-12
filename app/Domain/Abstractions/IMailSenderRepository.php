<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\MailSender;

interface IMailSenderRepository
{
    /** @return MailSender[] */
    public function findAll(): array;
    public function findById(int $id): ?MailSender;
    public function findByEmail(string $email): ?MailSender;
    public function findActive(): ?MailSender;
    public function create(MailSender $sender): MailSender;
    // Si el password de la entidad es null se conserva el guardado
    public function update(MailSender $sender, bool $resetTested): MailSender;
    public function delete(int $id): void;
    // Deja en uso solo esta cuenta; con null ninguna (se usa la del .env)
    public function setActive(?int $id): void;
    public function markTested(int $id): void;
}
