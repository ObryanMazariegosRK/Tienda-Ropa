<?php

namespace App\Application\UseCases\Mail;

use App\Application\Abstractions\Mail\IListMailSendersUseCase;
use App\Application\DTOs\Mail\MailSenderDTO;
use App\Application\DTOs\Mail\MailSettingsDTO;
use App\Domain\Abstractions\IMailSenderRepository;

class ListMailSendersUseCase implements IListMailSendersUseCase
{
    public function __construct(private IMailSenderRepository $repository) {}

    public function execute(): MailSettingsDTO
    {
        return new MailSettingsDTO(
            senders: array_map(fn($s) => MailSenderDTO::fromEntity($s), $this->repository->findAll()),
            defaultEmail: config('mail.from.address')
        );
    }
}
