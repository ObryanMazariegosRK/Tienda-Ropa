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
            //Armamos el DTO con el correo validado
            $dto = new ForgotPasswordDTO(
                email: $request->validated('email')
            );

            //Ejecutamos el caso de uso
            $this->forgotPasswordUseCase->execute($dto);

            //Devolvemos siempre un mensaje de éxito para evitar enumeración de correos
            return response()->json([
                'message' => 'Si el correo existe en nuestro sistema, hemos enviado un código de recuperación.'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}