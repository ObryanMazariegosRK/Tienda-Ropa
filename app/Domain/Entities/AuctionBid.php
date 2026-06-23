<?php

namespace App\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class AuctionBid
{
    private ?int $id;
    private int $auctionId;
    private int $userId;
    private float $amount;
    private DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        int $auctionId,
        int $userId,
        float $amount,
        ?DateTimeImmutable $createdAt = null 
    ) {
        $this->validateAuctionId($auctionId);
        $this->validateUserId($userId);
        $this->validateAmount($amount);

        $this->id = $id;
        $this->auctionId = $auctionId;
        $this->userId = $userId;
        $this->amount = $amount;
        //Si no viene fecha (es una puja nueva), asignamos la fecha y hora actual
        $this->createdAt = $createdAt ?? new DateTimeImmutable(); 
    }

    //VALIDACIONES

    private function validateAuctionId(int $auctionId): void {
        if ($auctionId <= 0) {
            throw new InvalidArgumentException('La subasta es obligatoria.');
        }
    }

    private function validateUserId(int $userId): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException('El usuario es inválido.');
        }
    }

    private function validateAmount(float $amount): void {
        if ($amount <= 0) {
            throw new InvalidArgumentException('La oferta debe ser mayor a cero.');
        }
    }

    //GETTERS
    public function getId(): ?int {
        return $this->id;
    }

    public function getAuctionId(): int {
        return $this->auctionId;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCreatedAt(): DateTimeImmutable {
        return $this->createdAt;
    }
}