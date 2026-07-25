<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\Address;

interface IAddressRepository
{
    public function create(Address $address): Address;
    public function update(Address $address): Address;
    public function findById(int $id): ?Address;
    public function findByIdAndUser(int $id, int $userId): ?Address;
    public function findByUserId(int $userId): array;
    public function delete(int $id): bool;
    public function clearDefaultForUser(int $userId): void;
}