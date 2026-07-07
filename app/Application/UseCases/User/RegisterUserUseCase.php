<?php

namespace App\Application\UseCases\User;

use App\Application\Abstractions\User\IRegisterUserUseCase;
use App\Domain\Abstractions\User\IPasswordHasher;
use App\Domain\Abstractions\User\IEmailService;
use App\Application\DTOs\User\RegisterUserDTO;
use App\Domain\Entities\User;
use App\Domain\Enum\RoleType;
use App\Domain\Abstractions\User\IUserRepository;
use Exception;

class RegisterUserUseCase implements IRegisterUserUseCase{

    public function __construct(
        private IUserRepository $userRepository,
        private IPasswordHasher $passwordHasher,
        private IEmailService $emailService
    ){}

    public function execute(RegisterUserDTO $dto):void{

        //Verificamos si el correo ya esta registrado
        if($this->userRepository->existsByEmail($dto->email)){
            throw new Exception("El correo {$dto->email} ya está registrado.");
        }

        //Encriptamos la contraseña
        $hashedPassword= $this->passwordHasher->hashPassword($dto->password);

        //Creamos la entidad del usuario
        $userEntity=new User(
            id:null,
            name: $dto->name,
            lastName: $dto->lastName,
            email: $dto->email,
            password: $hashedPassword,
            phone: $dto->phone,
            role: RoleType::CUSTOMER

        );

        //Generamos el código de 6 digitos
        $verificationCode = (string) rand(100000, 999999);

        //Calculamos la expiración 
        $expiresAt = new \DateTimeImmutable('+15 minutes');

        //Le pasamos el código y la fecha a la Entidad para que se lo apropie
        $userEntity->assignNewVerificationCode($verificationCode, $expiresAt);

        //Guardamos el usuario en la db
        $this->userRepository->addUser($userEntity);

        //Ordenamos el envío del correo de bienvenida
        $this->emailService->sendVerificationCode(
            $userEntity->getEmail(),
            $userEntity->getFullName(),
            $verificationCode
        );

    }



}




