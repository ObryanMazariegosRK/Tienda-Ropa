<?php

namespace App\Http\Controllers;

use App\Application\Abstractions\Banner\IGetActiveBannerGroupUseCase;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function __construct(private IGetActiveBannerGroupUseCase $getActive) {}

    public function index(): JsonResponse
    {
        $group = $this->getActive->execute();
        return response()->json(['success' => true, 'data' => $group], 200); // data será null si no hay ninguno activo
    }
}