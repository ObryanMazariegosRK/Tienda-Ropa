<?php
// IAddMediaToGroupUseCase.php
namespace App\Application\Abstractions\Banner;
use App\Application\DTOs\Banner\BannerGroupDTO;
interface IAddMediaToGroupUseCase { public function execute(int $groupId, array $files): BannerGroupDTO; }