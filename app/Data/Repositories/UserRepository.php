<?php

namespace App\Data\Repositories;

use App\Domain\Abstractions\User\IUserRepository;
use App\Domain\Entities\User;
use App\Models\User as UserModel;

class UserRepository implements IUserRepository{

    public function findUserByEmail(string $email):? User{
        //Buscamos el usuario en la db
        $User= UserModel::where('email', $email)->first();

        if(!$User){
            return null;
        }

        //convertimos el modelo Eloquent a una entidad
        return new User(
            id: $User->id,
            name: $User->name,
            lastName: $User->last_name,
            email: $User->email,
            password: $User->password,
            phone: $User->phone,
            role: $User->role,
            emailVerifiedAt: $User->email_verified_at?->toDateTimeImmutable(),
            verificationCode: $User->verification_code,
            verificationCodeExpiresAt: $User->verification_code_expires_at?->toDateTimeImmutable()
        );
    }

    public function existsByEmail(string $email): bool{
        return UserModel::where('email', $email)->exists();
    }

    public function addUser(User $user):void{

        UserModel::create([
            'name' => $user->getName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            //Viene encriptada desde el Caso de Uso
            'password' => $user->getPassword(), 
            'phone' => $user->getPhone(),
            //Pasamos el Enum directamente, el Modelo de Laravel lo convierte a string para la DB
            'role' => $user->getRole(),
            'email_verified_at' => $user->getEmailVerifiedAt(),
            'verification_code' => $user->getVerificationCode(),
            'verification_code_expires_at' => $user->getVerificationCodeExpiresAt(),
        ]);
    }

    public function updateUser(User $user):void{

        $User= UserModel::find($user->getId());

        if($User){
            $User->update([
                'name' => $user->getName(),
                'last_name' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'password' => $user->getPassword(),
                'role' => $user->getRole(),
                'email_verified_at' => $user->getEmailVerifiedAt(),
                'verification_code' => $user->getVerificationCode(),
                'verification_code_expires_at' => $user->getVerificationCodeExpiresAt(),
            ]);
        }



    }

    public function createToken(User $user, bool $rememberMe = false): string {

        //Buscamos el modelo Eloquent y usamos el email porue sabemos 
        //que existe ya que ya paso la validación en el caso de uso
        $userModel = UserModel::where('email', $user->getEmail())->first();
 
        //Definimos el tiempo de vida del token
        $expiresAt = $rememberMe ? now()->addDays(30) : now()->addHours(2);

        //Generamos el token de Sanctum pasándole el nombre, los permisos y la fecha de expiración
        return $userModel->createToken(
            'auth_token', 
            ['*'], //Permisos por defecto (todos)
            $expiresAt
        )->plainTextToken;
    }

    public function findUserById(int $id): ?User{
        
        $User= UserModel::find($id);

        if(!$User){
            return null;
        }

        return new User(
            id: $User->id,
            name: $User->name,
            lastName: $User->last_name,
            email: $User->email,
            password: $User->password,
            phone: $User->phone,
            role: $User->role,
            emailVerifiedAt: $User->email_verified_at?->toDateTimeImmutable(),
            verificationCode: $User->verification_code,
            verificationCodeExpiresAt: $User->verification_code_expires_at?->toDateTimeImmutable()
        );
    }





}