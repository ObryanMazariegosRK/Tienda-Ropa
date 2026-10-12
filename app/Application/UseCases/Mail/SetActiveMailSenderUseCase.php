<?php

namespace App\Application\UseCases\Mail;

use App\Application\Abstractions\Mail\ISetActiveMailSenderUseCase;
use App\Domain\Abstractions\IMailSenderRepository;
use InvalidArgumentException;

class SetActiveMailSenderUseCase implements ISetActiveMailSenderUseCase
{
    public function __construct(private IMailSenderRepository $repository) {}

    public function execute(?int $id): void
    {
        if ($id !== null) {
            $sender = $this->repository->findById($id);
            if (!$sender) {
                throw new InvalidArgumentException('La cuenta de correo no existe.');
            }
            // Evita dejar a los clientes sin códigos por una contraseña mal escrita
            if (!$sender->wasTested()) {
                throw new InvalidArgumentException('Primero envía un correo de prueba con esta cuenta para comprobar que funciona.');
            }
        }

        $this->repository->setActive($id);
    }
}
