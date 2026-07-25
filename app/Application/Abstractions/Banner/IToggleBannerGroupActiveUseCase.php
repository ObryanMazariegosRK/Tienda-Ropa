<?php
// IToggleBannerGroupActiveUseCase.php
namespace App\Application\Abstractions\Banner;
interface IToggleBannerGroupActiveUseCase { public function execute(int $groupId): array; }