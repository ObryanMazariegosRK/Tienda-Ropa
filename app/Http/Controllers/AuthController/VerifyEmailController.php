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
            //Armamos el DTO con los datos limpios que pasaron la validación del Request
            $dto = new VerifyEmailDTO(
                email: $request->validated('email'),
                code: $request->validated('code')
            );

            //Ejecutamos el caso de uso
            $this->verifyEmailUseCase->execute($dto);

            //Respondemos con éxito
            return response()->json([
                'message' => 'Correo electrónico verificado con éxito. Ya puedes iniciar sesión.'
            ], 200);

        } catch (Exception $e) {
            //Si el código expiró, es inválido o el usuario no existe, atrapamos la excepción
            return response()->json([
                'error' => $e->getMessage()
            ], 400); // 400 Bad Request
        }
    }
}