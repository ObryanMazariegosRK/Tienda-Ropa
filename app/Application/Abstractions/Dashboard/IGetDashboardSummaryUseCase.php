<?php
namespace App\Application\Abstractions\Dashboard;
use App\Application\DTOs\Dashboard\DashboardSummaryDTO;

interface IGetDashboardSummaryUseCase {
    public function execute(): DashboardSummaryDTO;
}