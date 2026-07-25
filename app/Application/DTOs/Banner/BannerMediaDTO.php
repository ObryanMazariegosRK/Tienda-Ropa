<?php

namespace App\Application\DTOs\Banner;

class BannerMediaDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $mediaUrl
    ) {}
}