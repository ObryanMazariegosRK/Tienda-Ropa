<?php
// IDeleteBannerGroupUseCase.php
namespace App\Application\Abstractions\Banner;
interface IDeleteBannerGroupUseCase { public function execute(int $groupId): void; }