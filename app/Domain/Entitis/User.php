<?php

namespace App\Domain\Entitis;

use App\Domain\Enum\RoleType;
use InvalidArgumentException;



class User{
    private int $id;
    private string $name;
    private string $lastname;
    private string $email;
    private string $password;
    private string $phone;
    private RoleType $role;

    public function _construct(
        ?int $id,
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $phone,
        RoleType $role
    ){
        validateFirstName($firstName);
        validateLastName($lastName);
        validateEmail($email);
        validatePassword($password);
        validatePhone($phone);


        $this->id=$id;
        $this->firstName=$firstName;
        $this->lastName=$lastName;
        $this->email=$email;
        $this->password=$password;
        $this->phone=$phone;
        $this->role=$role;

    }


    //VALIDACIONES

    private function validateFirstName(string $firstName):void{
        if(empty(trim($firstName))){
            throw new InvalidArgumentException('El nombre no puede estar vacío');
        }
        
        if(strlen($firstName)>100){
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

    /*
    private function validateAddress(string $address):void{

        if(empty(trim($address))){
            throw new InvalidArgumentException('La dirección no puede estar vacia');
        }

        if(strlen($address)>255){
            throw new InvalidArgumentException('La dirección no puede tener más de 255 caracteres');
        }
    }*/

    public function changePhone(string $phone):void{
        $this->validatePhone($phone);
        $this->phone=$phone;
    }

    public function changeAddress(string $address):void{
        $this->validateAddress($address);
        $this->address=$address;
    }

    public function changeRole(RoleType $role):void{
        $this->role=$role;
    }

        //GETTERS

    public function getId():?int{
        return $this->id;
    }

    public function getFirstName():string{
        return $this->firstName;
    }

    public function getLastName():string{
        return $this->lastName;
    }

    public function getFullName():string{
        return "{this->firstName} {this->lastName}";
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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getRole(): RoleType
    {
        return $this->role;
    }



















}