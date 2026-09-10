<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Application\DTOs\User\VerifyEmailDTO;
use App\Application\Abstractions\User\IVerifyEmailUseCase;
use Illuminate\Http\JsonResponse;
use Exception;

class VerifyEmailController extends Controller
{
    public function __construct(
        private IVerifyEmailUseCase $verifyEmailUseCase
    ) {}

    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $dto = new VerifyEmailDTO(
                email: $request->validated('email'),
                code: $request->validated('code')
            );

            $token = $this->verifyEmailUseCase->execute($dto);

            return response()->json([
                'message' => 'Correo electrónico verificado con éxito.',
                'token' => $token
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}