<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Requests\RegisterUserRequest;
use App\Application\DTOs\User\RegisterUserDTO;
use App\Application\Abstractions\User\IRegisterUserUseCase;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;

class RegisterUserController extends Controller
{
    //Inyectamos la Interfaz del Caso de Uso
    public function __construct(
        private readonly IRegisterUserUseCase $registerUserUseCase
    ) {}

    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        try {
            //Empaquetamos los datos validados en el DTO
            $dto = new RegisterUserDTO(
                name: $request->validated('name'),
                lastName: $request->validated('last_name'),
                email: $request->validated('email'),
                password: $request->validated('password'),
                phone: $request->validated('phone')
            );

            //Ejecutamos el Caso de Uso
            $this->registerUserUseCase->execute($dto);

            //Retornamos respuesta exitosa
            return response()->json([
                'message' => 'Usuario registrado exitosamente.'
            ], 201);

        } catch (Exception $e) {
            //Si el correo ya existe u ocurre un error en el Dominio
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}