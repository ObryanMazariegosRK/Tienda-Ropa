<?php
// ToggleBannerGroupActiveUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IToggleBannerGroupActiveUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;
use Exception;

class ToggleBannerGroupActiveUseCase implements IToggleBannerGroupActiveUseCase
{
    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(int $groupId): array
    {
        $group = $this->repo->findById($groupId);
        if (!$group) throw new Exception('Grupo no encontrado.');

        if ($group->isActive()) {
            // Desactivar es libre, no afecta a nadie más
            $this->repo->deactivateAll(); // simplifica: solo puede haber 1 activo, así que esto lo apaga
        } else {
            // Activar uno implica apagar todos los demás primero ("radio button")
            $this->repo->deactivateAll();
            $this->repo->activateOne($groupId);
        }

        return array_map(fn($g) => BannerGroupDTO::fromEntity($g), $this->repo->findAll());
    }
}