<?php

namespace App\Application\UseCases\Mail;

use App\Application\Abstractions\Mail\IDeleteMailSenderUseCase;
use App\Domain\Abstractions\IMailSenderRepository;
use InvalidArgumentException;

class DeleteMailSenderUseCase implements IDeleteMailSenderUseCase
{
    public function __construct(private IMailSenderRepository $repository) {}

    // Si era la cuenta en uso, los correos vuelven a salir con la cuenta del .env
    public function execute(int $id): void
    {
        if (!$this->repository->findById($id)) {
            throw new InvalidArgumentException('La cuenta de correo no existe.');
        }
        $this->repository->delete($id);
    }
}
