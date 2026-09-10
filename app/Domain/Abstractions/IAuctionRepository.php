<?php
namespace App\Domain\Abstractions;
use App\Domain\Entities\Auction;

interface IAuctionRepository
{
    public function create(Auction $auction): Auction;
    public function findById(int $id): ?Auction;
    public function findByProductId(int $productId): ?Auction;
    public function update(Auction $auction): Auction;
    public function findExpiredActive(): array; // para el comando de cierre automático
    public function findUnclaimedWins(): array;
    public function findByOrderId(int $orderId): ?Auction;
}