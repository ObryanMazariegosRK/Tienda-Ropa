<?php
namespace App\Application\Abstractions\Dashboard;

interface IGetTopCategoriesUseCase
{
    public function execute(string $startDate, string $endDate): array;
}