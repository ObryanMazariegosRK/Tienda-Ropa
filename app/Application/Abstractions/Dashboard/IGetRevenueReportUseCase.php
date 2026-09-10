<?php
namespace App\Application\Abstractions\Dashboard;
use App\Application\DTOs\Dashboard\RevenueReportDTO;

interface IGetRevenueReportUseCase {
    public function execute(string $startDate, string $endDate): RevenueReportDTO;
}