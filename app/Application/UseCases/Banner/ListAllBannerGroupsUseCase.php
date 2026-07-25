<?php

// ListAllBannerGroupsUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IListAllBannerGroupsUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;

class ListAllBannerGroupsUseCase implements IListAllBannerGroupsUseCase
{
    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(): array
    {
        return array_map(fn($g) => BannerGroupDTO::fromEntity($g), $this->repo->findAll());
    }
}