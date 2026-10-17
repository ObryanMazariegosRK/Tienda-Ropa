<?php
namespace App\Application\Abstractions\Bitacora;

use App\Application\DTOs\Bitacora\EventoBitacoraDTO;

interface IRegistrarEventoBitacoraUseCase
{
    public function execute(EventoBitacoraDTO $evento): void;
}
