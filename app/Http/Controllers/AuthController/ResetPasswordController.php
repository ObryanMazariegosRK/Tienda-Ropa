<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Requests\ResetPasswordRequest;
use App\Http\Controllers\Controller;
use App\Application\DTOs\User\ResetPasswordDTO;
use App\Application\Abstractions\User\IResetPasswordUseCase;
use Illuminate\Http\JsonResponse;
use Exception;

class ResetPasswordController extends Controller
{
    public function __construct(
        private IResetPasswordUseCase $resetPasswordUseCase
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $dto = new ResetPasswordDTO(
                email: $request->validated('email'),
                code: $request->validated('code'),
                password: $request->validated('password')
            );

            $token = $this->resetPasswordUseCase->execute($dto);

            return response()->json([
                'message' => 'Tu contraseña ha sido restablecida exitosamente.',
                'token' => $token
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}