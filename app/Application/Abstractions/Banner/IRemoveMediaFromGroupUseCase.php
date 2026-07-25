<?php
namespace App\Application\Abstractions\Banner;
use App\Application\DTOs\Banner\BannerGroupDTO;

interface IRemoveMediaFromGroupUseCase {
    public function execute(int $groupId, int $mediaId): ?BannerGroupDTO;
}