<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Controllers\Controller;
use App\Application\DTOs\User\ForgotPasswordDTO;
use App\Application\Abstractions\User\IForgotPasswordUseCase;
use Illuminate\Http\JsonResponse;
use Exception;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private IForgotPasswordUseCase $forgotPasswordUseCase
    ) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $dto = new ForgotPasswordDTO(
                email: $request->validated('email')
            );

            $this->forgotPasswordUseCase->execute($dto);

            return response()->json([
                'message' => 'Si el correo existe en nuestro sistema, hemos enviado un código de recuperación.'
            ], 200);

        } catch (\App\Domain\Exceptions\BusinessRuleException $e) {
            
            return response()->json(['error' => $e->getMessage()], 409);

        } catch (Exception $e) {
            
            return response()->json([
                'message' => 'Ocurrió un error al procesar la solicitud.'
            ], 500);
        }
    }
}