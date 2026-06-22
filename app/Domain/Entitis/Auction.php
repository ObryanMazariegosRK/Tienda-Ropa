<?php

namespace App\Domain\Entities;

use App\Domain\Enum\AuctionStatus;
use DateTimeImmutable;
use InvalidArgumentException;

class Auction
{
    private ?int $id;
    private int $productId;
    private float $startingPrice;
    private float $currentPrice;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;
    private AuctionStatus $status;
    private ?int $currentWinnerUserId;
    private ?int $winnerUserId;

    public function __construct(
        ?int $id,
        int $productId,
        float $startingPrice,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        AuctionStatus $status = AuctionStatus::ACTIVE,
        ?int $currentWinnerUserId = null,
        ?int $winnerUserId = null
    ) {
        $this->validateProductId($productId);
        $this->validatePrice($startingPrice);
        $this->validateDates($startDate, $endDate);

        $this->id = $id;
        $this->productId = $productId;
        $this->startingPrice = $startingPrice;
        $this->currentPrice = $startingPrice;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
        $this->currentWinnerUserId = $currentWinnerUserId;
        $this->winnerUserId = $winnerUserId;
    }

    // =========================
    // VALIDACIONES
    // =========================

    private function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException(
                'El producto es obligatorio.'
            );
        }
    }

    private function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidArgumentException(
                'El precio debe ser mayor a cero.'
            );
        }
    }

    private function validateDates(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): void {
        if ($endDate <= $startDate) {
            throw new InvalidArgumentException(
                'La fecha de finalización debe ser posterior a la fecha de inicio.'
            );
        }
    }

    // =========================
    // REGLAS DE NEGOCIO
    // =========================

    public function registerBid(
        int $userId,
        float $amount
    ): void {
        if (!$this->isActive()) {
            throw new InvalidArgumentException(
                'La subasta no está activa.'
            );
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'El usuario es inválido.'
            );
        }

        if ($amount <= $this->currentPrice) {
            throw new InvalidArgumentException(
                'La oferta debe ser mayor a la actual.'
            );
        }

        $this->currentPrice = $amount;
        $this->currentWinnerUserId = $userId;
    }

    public function finish(): void
    {
        $this->status = AuctionStatus::FINISHED;
        $this->winnerUserId = $this->currentWinnerUserId;
    }

    public function cancel(): void
    {
        $this->status = AuctionStatus::CANCELLED;
    }

    // =========================
    // CONSULTAS
    // =========================

    public function isActive(): bool
    {
        return $this->status === AuctionStatus::ACTIVE;
    }

    public function isFinished(): bool
    {
        return $this->status === AuctionStatus::FINISHED;
    }

    public function isCancelled(): bool
    {
        return $this->status === AuctionStatus::CANCELLED;
    }

    public function hasWinner(): bool
    {
        return $this->winnerUserId !== null;
    }

    // =========================
    // GETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getStartingPrice(): float
    {
        return $this->startingPrice;
    }

    public function getCurrentPrice(): float
    {
        return $this->currentPrice;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getStatus(): AuctionStatus
    {
        return $this->status;
    }

    public function getCurrentWinnerUserId(): ?int
    {
        return $this->currentWinnerUserId;
    }

    public function getWinnerUserId(): ?int
    {
        return $this->winnerUserId;
    }
}