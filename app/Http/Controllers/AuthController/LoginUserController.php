<?php

namespace App\Http\Controllers\AuthController;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Application\DTOs\User\LoginUserDTO;
use App\Application\Abstractions\User\ILoginUserUseCase;
use Exception;
use Illuminate\Http\JsonResponse;;

class LoginUserController extends Controller{

    //Inyectamos la interfaz del caso de uso 
    public function __construct(
        private readonly ILoginUserUseCase $loginUserUseCase
    ){}

    public function __invoke(LoginUserRequest $request): JsonResponse{
        try{
            //Armamos el DTO
            $dto= new LoginUserDTO(
                email: $request->validated('email'),
                password: $request->validated('password'),
                rememberMe: $request->boolean('remember_me')
            );

            //Ejecutamos el caso de uso
            $token= $this->loginUserUseCase->execute($dto);

            //retornamos el token 
            return response()->json([
                'message'=>'Inicio de sesión exitoso',
                'token'=>$token
            ], 200);
        }catch(Exception $e){
            //Interceptamos si el error es de verificación (Código 403)
            if($e->getCode() === 403) {
                return response()->json([
                    'status' => 'not_verified',
                    'message' => $e->getMessage(),
                    'email' => $request->email //Tomamos el correo del DTO
                ], 403);
            }
            //401 No autorizado, credenciales incorrectas
            return response()->json([
                'error'=>$e->getMessage()
            ], 401);

        }




    }



}