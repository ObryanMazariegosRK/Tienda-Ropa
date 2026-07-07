<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResendCodeRequest;
use App\Application\DTOs\User\ResendCodeDTO;
use App\Application\Abstractions\User\IResendVerificationCodeUseCase;
use Illuminate\Http\JsonResponse;
use Exception;

class ResendVerificationCodeController extends Controller
{
    public function __construct(
        private IResendVerificationCodeUseCase $resendVerificationCodeUseCase
    ) {}

    public function __invoke(ResendCodeRequest $request): JsonResponse
    {
        try {
            $dto = new ResendCodeDTO(email: $request->validated('email'));

            $this->resendVerificationCodeUseCase->execute($dto);

            return response()->json([
                'message' => 'Se ha enviado un nuevo código de verificación a tu correo.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}