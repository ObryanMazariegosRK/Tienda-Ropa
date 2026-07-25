<?php

namespace App\Domain\Abstractions;

use App\Domain\Entities\BannerGroup;

interface IBannerGroupRepository
{
    public function create(BannerGroup $group): BannerGroup;
    public function findById(int $id): ?BannerGroup;
    public function findAll(): array;
    public function findActive(): ?BannerGroup;
    public function delete(int $id): bool;
    public function countGroups(): int;
    public function deactivateAll(): void;
    public function activateOne(int $id): void;
    public function renameGroup(int $id, string $name): void;
    public function addMedia(int $groupId, string $mediaUrl, int $order): void;
    public function removeMedia(int $mediaId): ?string;
    public function countMedia(int $groupId): int;
}