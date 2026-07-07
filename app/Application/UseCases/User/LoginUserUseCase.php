<?php

namespace App\Application\UseCases\User;

use App\Application\Abstractions\User\ILoginUserUseCase;
use App\Application\DTOs\User\LoginUserDTO;
use App\Domain\Abstractions\User\IUserRepository;
use App\Domain\Abstractions\User\IPasswordHasher;
use Exception;

class LoginUserUseCase implements ILoginUserUseCase{

    public function __construct(

        private IUserRepository $userRepository,
        private IPasswordHasher $passwordHasher

    ){}

    public function execute(LoginUserDTO $dto): string{

        //Buscamos al usuario por su correo
        $userEntity= $this->userRepository->findUserByEmail($dto->email);

        if($userEntity===null){
            throw new Exception("Las credenciales proporcionadas son incorrectas");
        }

        $isPasswordValid= $this->passwordHasher->verifyPassword(
            $dto->password,
            $userEntity->getPassword()
        );

        if(!$isPasswordValid){
            throw new Exception("Las credenciales proporcionadas son incorrectas");
        }

        //Verificamos que el usuario haya validado su correo
        if(!$userEntity->isEmailVerified()){
            //
            throw new Exception("Debes verificar tu correo electrónico antes de poder iniciar sesión.", 403);
        }

        //Creamos el token
        $token = $this->userRepository->createToken($userEntity, $dto->rememberMe);

        return $token;
    }



}