<?php
// IGetActiveBannerGroupUseCase.php
namespace App\Application\Abstractions\Banner;
use App\Application\DTOs\Banner\BannerGroupDTO;
interface IGetActiveBannerGroupUseCase { public function execute(): ?BannerGroupDTO; }