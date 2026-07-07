<?php

namespace App\Application\UseCases\User;

use App\Application\Abstractions\User\IGetProfileUseCase;
use App\Application\DTOs\User\UserProfileDTO;
use App\Domain\Abstractions\User\IUserRepository;
use Exception;

class GetProfileUseCase implements IGetProfileUseCase{

    public function __construct(
        private IUserRepository $userRepository
    ){}

    public function execute(int $userId): UserProfileDTO{
        
        //Buscamos el usuario por su ID
        $userEntity=$this->userRepository->findUserById($userId);
        
        if($userEntity===null){
            throw new Exception("Usuario no encontrado", 404);
        }

        //Convertimos la Entidad de Dominio en un DTO seguro 
        return UserProfileDTO::fromEntity($userEntity);

    }



}