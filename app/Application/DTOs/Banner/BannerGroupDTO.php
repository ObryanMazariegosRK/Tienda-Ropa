<?php

namespace App\Application\DTOs\Banner;

use App\Domain\Entities\BannerGroup;

class BannerGroupDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isActive,
        public readonly array $media
    ) {}

    public static function fromEntity(BannerGroup $group): self
    {
        return new self(
            id: $group->getId(),
            name: $group->getName(),
            type: $group->getType()->value,
            isActive: $group->isActive(),
            media: array_map(
                fn($m) => new BannerMediaDTO($m->getId(), $m->getMediaUrl()),
                $group->getMedia()
            )
        );
    }
}