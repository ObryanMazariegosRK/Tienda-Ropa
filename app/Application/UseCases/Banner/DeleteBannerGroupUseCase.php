<?php
// DeleteBannerGroupUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IDeleteBannerGroupUseCase;
use App\Domain\Abstractions\IBannerGroupRepository;
use Illuminate\Support\Facades\Storage;
use Exception;

class DeleteBannerGroupUseCase implements IDeleteBannerGroupUseCase
{
    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(int $groupId): void
    {
        $group = $this->repo->findById($groupId);
        if (!$group) throw new Exception('Grupo no encontrado.');

        foreach ($group->getMedia() as $media) {
            Storage::disk('public')->delete($media->getMediaUrl());
        }

        $this->repo->delete($groupId); // ON DELETE CASCADE limpia banners hijos en la BD
    }
}