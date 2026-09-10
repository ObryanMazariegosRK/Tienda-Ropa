<?php
namespace App\Application\Abstractions\Dashboard;
use App\Application\DTOs\Dashboard\OrderStatsDTO;

interface IGetOrderStatsUseCase {
    public function execute(): OrderStatsDTO;
}