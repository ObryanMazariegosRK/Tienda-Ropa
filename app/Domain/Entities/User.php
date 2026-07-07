<?php

namespace App\Domain\Entities;

use App\Domain\Enum\RoleType;
use InvalidArgumentException;
use PhpParser\Node\Expr\NullsafeMethodCall;

class User{
    private ?int $id;
    private string $name;
    private string $lastName;
    private string $email;
    private string $password;
    private string $phone;
    private RoleType $role;
    private ?\DateTimeImmutable $emailVerifiedAt;
    private ?string $verificationCode;
    private ?\DateTimeImmutable $verificationCodeExpiresAt;

    public function __construct(
        ?int $id,
        string $name,
        string $lastName,
        string $email,
        string $password,
        string $phone,
        RoleType $role,
        ?\DateTimeImmutable $emailVerifiedAt= null,
        ?string $verificationCode=null,
        ?\DateTimeImmutable $verificationCodeExpiresAt=null
    ){
        $this->validateFirstName($name);
        $this->validateLastName($lastName);
        $this->validateEmail($email);
        $this->validatePassword($password);
        $this->validatePhone($phone);


        $this->id=$id;
        $this->name=$name;
        $this->lastName=$lastName;
        $this->email=$email;
        $this->password=$password;
        $this->phone=$phone;
        $this->role=$role;

        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->verificationCode = $verificationCode;
        $this->verificationCodeExpiresAt = $verificationCodeExpiresAt;

    }


    //VALIDACIONES

    private function validateFirstName(string $name):void{
        if(empty(trim($name))){
            throw new InvalidArgumentException('El nombre no puede estar vacío');
        }
        
        if(strlen($name)>100){
            throw new InvalidArgumentException('El nombre no puede tener más de 100 caracteres');
        }

    }

    private function validateLastName(string $lastName):void{

        if(empty(trim($lastName))){
            throw new InvalidArgumentException('El apellido no puede estar vacío');
        }
        if(strlen($lastName)>100){
            throw new InvalidArgumentException('El apellido no puede tener más de 100 caracteres');
        }

    }

    private function validateEmail(string $email):void{
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            throw new InvalidArgumentException('El email no es válido');
        }
    }

    private function validatePassword(string $password):void{

        if(empty(trim($password))){
            throw new InvalidArgumentException('La contraseña no puede estar vacia');
        }

    }

    private function validatePhone(string $phone):void{

        if(empty(trim($phone))){
            throw new InvalidArgumentException('El teléfono no puede estar vacio');
        }

        if(strlen($phone)<8){
            throw new InvalidArgumentException('El teléfono no puede tener menos de 8 caracteres');
        }
    }




    public function changePhone(string $phone):void{
        $this->validatePhone($phone);
        $this->phone=$phone;
    }

    public function changePassword(string $newPassword): void
    {
        $this->validatePassword($newPassword);
        $this->password = $newPassword;
        //limpiamos (eliminamos xd) el codigo de la db
        $this->verificationCode=null;
        $this->verificationCodeExpiresAt=null;
    }

    public function changeRole(RoleType $role):void{
        $this->role=$role;
    }

    //Para saber si el usuario ya esta verificado
    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    //Comprobar si el código que envió el usuario es correcto y no ha caducado
    public function isVerificationCodeValid(string $code, \DateTimeImmutable $now): bool
    {
        //Si no hay código activo guardado en el usuario
        if ($this->verificationCode === null || $this->verificationCodeExpiresAt === null) {
            return false;
        }

        //Si el código no coincide exactamente
        if ($this->verificationCode !== $code) {
            return false;
        }

        //Si la fecha/hora actual superó a la fecha de expiración
        if ($now > $this->verificationCodeExpiresAt) {
            return false;
        }

        return true;
    }

    //Acción para marcar la cuenta como verificada
    public function markEmailAsVerified(\DateTimeImmutable $verifiedAt): void
    {
        $this->emailVerifiedAt = $verifiedAt;
        
        //Destruimos el código por seguridad para que no se pueda reusar
        $this->verificationCode = null;
        $this->verificationCodeExpiresAt = null;
    }

    //Acción para generar/asignar un nuevo código (por si pide que se lo reenviemos)
    public function assignNewVerificationCode(string $code, \DateTimeImmutable $expiresAt): void
    {
        $this->verificationCode = $code;
        $this->verificationCodeExpiresAt = $expiresAt;
    }



    //GETTERS

    public function getId():?int{
        return $this->id;
    }

    public function getName():string{
        return $this->name;
    }

    public function getLastName():string{
        return $this->lastName;
    }

    public function getFullName():string{
        return "{$this->name} {$this->lastName}";
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    /*
    public function getAddress(): string
    {
        return $this->address;
    }
    */

    public function getRole(): RoleType
    {
        return $this->role;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable { return $this->emailVerifiedAt; }
    public function getVerificationCode(): ?string { return $this->verificationCode; }
    public function getVerificationCodeExpiresAt(): ?\DateTimeImmutable { return $this->verificationCodeExpiresAt; }

}