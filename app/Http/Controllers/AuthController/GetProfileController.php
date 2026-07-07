<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Controllers\Controller;
use App\Application\Abstractions\User\IGetProfileUseCase;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetProfileController extends Controller{

    public function __construct(
        private readonly IGetProfileUseCase $getProfileUseCase
    )
    {}

    public function __invoke(request $request): JsonResponse{

        try{
            //extraemos el id que el mddleware inyectó
            $userId=(int) $request->user()->id;
            //Pasamos el ID al caso de uso, el cual nos regresará un DTO
            $profileDTO = $this->getProfileUseCase->execute($userId);

            return response()->json($profileDTO, 200);

        }catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }


    }


}