<?php
// RenameBannerGroupUseCase.php
namespace App\Application\UseCases\Banner;

use App\Application\Abstractions\Banner\IRenameBannerGroupUseCase;
use App\Application\DTOs\Banner\BannerGroupDTO;
use App\Domain\Abstractions\IBannerGroupRepository;
use Exception;

class RenameBannerGroupUseCase implements IRenameBannerGroupUseCase
{
    public function __construct(private IBannerGroupRepository $repo) {}

    public function execute(int $groupId, string $name): BannerGroupDTO
    {
        $group = $this->repo->findById($groupId);
        if (!$group) throw new Exception('Grupo no encontrado.');

        $group->rename($name); // valida longitud/vacío en el dominio
        $this->repo->renameGroup($groupId, $name);

        return BannerGroupDTO::fromEntity($this->repo->findById($groupId));
    }
}