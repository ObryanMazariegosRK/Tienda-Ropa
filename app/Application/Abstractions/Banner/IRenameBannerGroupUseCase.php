<?php
// IRenameBannerGroupUseCase.php
namespace App\Application\Abstractions\Banner;
use App\Application\DTOs\Banner\BannerGroupDTO;
interface IRenameBannerGroupUseCase { public function execute(int $groupId, string $name): BannerGroupDTO; }