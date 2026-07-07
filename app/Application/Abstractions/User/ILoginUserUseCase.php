<?php
namespace App\Application\Abstractions\User;
use App\Application\DTOs\User\LoginUserDTO;

interface ILoginUserUseCase{

    /**
     * @param LoginUserDTO $dto
     * @return string (el token de acceso)
     * @throws \Exception en caso de que sean invalidas las credenciales
     */

    public function execute(LoginUserDTO $dto): string;
}