<?php
namespace App\Application\Abstractions\Dashboard;

use App\Application\DTOs\Dashboard\RevenueBySaleTypeDTO;

interface IGetRevenueBySaleTypeUseCase
{
    public function execute(string $startDate, string $endDate): RevenueBySaleTypeDTO;
}