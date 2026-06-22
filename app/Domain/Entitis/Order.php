<?php

namespace App\Domain\Entitis;
use DateTimeInmutable;
use App\Domain\Enum\OrderStatus;
USE InvalidArgumentException;



class Order{
    private ?int $id;
    private int $userId;
    private int $addressId;
    private string $shippingAddress;
    private float $total;
    private OrderStatus $status;
    private DateTimeInmutable $createdAt;

    public function __construct(

        ?int $id,
        int  $userId,
        int $addressId,
        string $shippingAddress,
        float $total,
        OrderStatus $status,
        DateTimeInmutable $createdAt

    ){
        $this->validateUserId($userId);
        $this->validateAddressId($addressId);
        $this->validateShippingAddress($shippingAddress);
        $this->validateTotal($total);

        $this->id=$id;
        $this->userId=$userId;
        $this->addressId=$addressId;
        $this->shippingAddress=$shippingAddress;
        $this->total=$total;
        $this->status=$status;
        $this->createdAt=$createdAt;

    }

    //VALIDACIONES

    private function validateUserId(int $userId):void{
        if($userId<=0){
            throw new InvalidArgumentException('El identificador es invalido');
        }
    }

    private function validateAddressId(int $addressId):void{

        if($addressId<=0){
            throw new InvalidArgumentException('El identificador de dirección es inválido');
        }
    }

    private function validateShippingAddress(string $shippingAddress):void{

        if(empty(trim($shippingAddress))){

            throw new InvalidArgumentException('La dirección de envío es obligatoria');

        }

        if(strlen($shippingAddress)>500){

            throw new InvalidArgumentException('La dirección de envío no puede superar los 500 caracteres');
        }


    }

    private function validateTotal(float $total):void{

        if($total<=0){
            throw new InvalidArgumentException('El total debe ser mayor a cero');
        }
    }

    public function confirm():void{
        $this->status=OrderStatus::CONFIRMED;
    }

    public function markInRoute():void{
        $this->status=OrderStatus::IN_ROUTE;
    }

    public function deliver():void{
        $this->status=OrderStatus::DELIVERED;
    }

    public function cancel():void{
        $this->status=OrderStatus::CANCELLED;
    }

    public function getId():?int{
        return $this->id;
    }

    public function getUserId():int{
        return $this->userId;
    }

    public function getAddressId():int{
        return $this->addressId;
    }

    public function getShippingAddress():string{
        return $this->shippingAddress;
    }

    public function getTotal():float{
        return $this->total;
    }

    public function getStatus():OrderStatus{
        return $this->status;
    }

    public function getCreatedAt():DateTimeInmutable{
        return $this->createdAt;
    }




    




















}