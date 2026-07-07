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
            //Mapeamos los datos validados al DTO
            $dto = new ResetPasswordDTO(
                email: $request->validated('email'),
                code: $request->validated('code'),
                password: $request->validated('password')
            );

            //Ejecutamos el caso de uso
            $this->resetPasswordUseCase->execute($dto);

            //Respuesta de éxito
            return response()->json([
                'message' => 'Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión.'
            ], 200);

        } catch (Exception $e) {
            //Capturamos los errores de negocio 
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}