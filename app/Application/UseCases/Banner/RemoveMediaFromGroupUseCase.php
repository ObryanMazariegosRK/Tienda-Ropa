<?php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IRemoveMediaFromGroupUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;
use Illuminate\Support\Facades\Storage;
use Exception;

class RemoveMediaFromGroupUseCase implements IRemoveMediaFromGroupUseCase
{
    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(int $groupId, int $mediaId): ?BannerGroupDTO
    {
        $group = $this->repo->findById($groupId);
        if (!$group) throw new Exception('Grupo no encontrado.');

        $url = $this->repo->removeMedia($mediaId);
        if ($url) {
            Storage::disk('public')->delete($url);
        }

        $remaining = $this->repo->findById($groupId);

        if (!$remaining || count($remaining->getMedia()) === 0) {
            // Ya no queda ningún archivo: eliminamos el grupo completo también
            $this->repo->delete($groupId);
            return null; // le indica al controller que el grupo desapareció
        }

        return BannerGroupDTO::fromEntity($remaining);
    }
}