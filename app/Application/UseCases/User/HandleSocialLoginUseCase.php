<?php

namespace App\Application\UseCases\User;

use App\Application\DTOs\User\SocialLoginDTO;
use App\Domain\Abstractions\User\IUserRepository;
use App\Domain\Entities\User;
use App\Domain\Enum\RoleType;

class HandleSocialLoginUseCase
{
    public function __construct(
        private IUserRepository $userRepository
    ) {}

    /** Devuelve el token de Sanctum, igual que LoginUserUseCase*/
    public function execute(SocialLoginDTO $dto): string
    {
        //Situación 1 ya entró antes con este proveedor
        $user = $this->userRepository->findUserByProvider($dto->provider, $dto->providerId);

        if ($user === null) {
            //SITUACIÓN 2t, iene cuenta con ese email, pero nunca usó Google
            $existing = $this->userRepository->findUserByEmail($dto->email);

            if ($existing !== null) {
                //Si la cuenta no estaba verificada, Google lo hace ahora y se descarta
                //la contraseña que pudo haber puesto otra persona con este correo
                if (!$existing->isEmailVerified()) {
                    $existing->claimWithVerifiedProvider(new \DateTimeImmutable());
                    $this->userRepository->updateUser($existing);
                }

                //Vinculamos su cuenta existente al proveedor (evita duplicados)
                $this->userRepository->linkProvider($existing->getId(), $dto->provider, $dto->providerId);
                $user = $existing;
            } else {
                //Situación 3 usuario nuevo, lo creamos
                $newUser = new User(
                    id: null,
                    name: $dto->name,
                    lastName: $dto->lastName ?? '',
                    email: $dto->email,
                    password: null, // sin contraseña
                    phone: null,  // sin teléfono aún
                    role: RoleType::CUSTOMER,             
                    emailVerifiedAt: new \DateTimeImmutable(), //ya verificado, Google confirmó el correo
                    verificationCode: null,
                    verificationCodeExpiresAt: null
                );

                $this->userRepository->addUser($newUser);

                //addUser devuelve void, así que lo recuperamos para tener su id
                $user = $this->userRepository->findUserByEmail($dto->email);
                $this->userRepository->linkProvider($user->getId(), $dto->provider, $dto->providerId);
            }
        }

        //Al final, para todos emitimos el token 
        return $this->userRepository->createToken($user, false);
    }
}