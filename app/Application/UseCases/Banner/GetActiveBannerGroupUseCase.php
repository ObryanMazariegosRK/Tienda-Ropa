<?php
// GetActiveBannerGroupUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IGetActiveBannerGroupUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;

class GetActiveBannerGroupUseCase implements IGetActiveBannerGroupUseCase
{
    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(): ?BannerGroupDTO
    {
        $group = $this->repo->findActive();
        return $group ? BannerGroupDTO::fromEntity($group) : null;
    }
}