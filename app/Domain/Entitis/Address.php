<?php

namespace App\Domain\Entitis;
use InvalidArgumentException;

class Address{
    private ?int $id;
    private int $userId;
    private string $label;
    private string $addressLine;
    private bool $isDefault;

    public function __construct(
        ?int $id,
        int $userId,
        string $label,
        string $addressLine,
        bool $isDefault
    ){

        $this->id=$id;
        $this->userId=$userId;
        $this->label=$label;
        $this->addressLine=$addressLine;
        $this->isDefault=$isDefault;

    }

    //VALIDACIONES
    private function validateUserId(int $userId):void{
        if($userId<=0){
            throw new InvalidArgumentException('El identificacodr es inválido');
        }
    }
    
    private function validateLabel(string $label):void{

        if(empty(trim($label))){
            throw new InvalidArgumentException('La etiqueta de la dirección es obligatorioa');

        }

        if(strlen($label)>50){

            throw new InvalidArgumentException('La etiqueta no puede superar los 50 caracteres');
        }
    }

    private function validateAddressLine(string $addressLine):void{

        if(empty(trim($addressLine))){
            throw new InvalidArgumentException('La direccion es obligatorio');
        }
        if(strlen($addressLine)>500){
            throw new InvalidArgumentException('La direccion no puede sueprar los 500 caracteres');
        }

    }

    public function changeLabel(string $label):void{
        $this->validateLabel($label);
        $this->label=$label;
    }

    public function changeAddressLine(string $addressLine):void{
        $this->validateAddressLine($addressLine);
        $this->addressLine=$addressLine;

    }

    public function markAsDefault():void{
        $this->isDefault=true;
    }

    public function removeAsDefault():void{
        $this->isDefault=false;
    }

    public function getId():?int{
        return $this->id;
    }

    public function getUserId(): int{
        return $this->userId;
    }

    public function getLabel():string{
        return $this->label;
    }

    public function getAddressLine():string{
        return $this->addressLine;
    }

    public function isDefault():bool{
        return $this->isDefault;
    }

}