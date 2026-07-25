<?php
// ICreateBannerGroupUseCase.php
namespace App\Application\Abstractions\Banner;
use App\Application\DTOs\Banner\BannerGroupDTO;
interface ICreateBannerGroupUseCase { public function execute(string $name, array $files): BannerGroupDTO; }