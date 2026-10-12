<?php

namespace App\Application\UseCases\Mail;

use App\Application\Abstractions\Mail\ISendTestMailUseCase;
use App\Domain\Abstractions\IMailSenderRepository;
use App\Domain\Abstractions\User\IEmailService;
use InvalidArgumentException;

class SendTestMailUseCase implements ISendTestMailUseCase
{
    public function __construct(
        private IMailSenderRepository $repository,
        private IEmailService $emailService
    ) {}

    public function execute(int $senderId, string $toEmail): void
    {
        $sender = $this->repository->findById($senderId);
        if (!$sender) {
            throw new InvalidArgumentException('La cuenta de correo no existe.');
        }

        // Si falla lanza RuntimeException con el motivo; solo se marca como probada si llegó
        $this->emailService->sendTestEmail($sender, $toEmail);
        $this->repository->markTested($senderId);
    }
}
