<?php

use App\Http\Controllers\AuthController\ForgotPasswordController;
use App\Http\Controllers\AuthController\GetProfileController;
use App\Http\Controllers\AuthController\LoginUserController;
use App\Http\Controllers\AuthController\RegisterUserController;
use App\Http\Controllers\AuthController\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController\ResendVerificationCodeController;
use App\Http\Controllers\AuthController\LogoutUserController;
use App\Http\Controllers\AuthController\ResetPasswordController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//CATEGORIAS
Route::get('/categories', [CategoryController::class, 'index']);

Route::post('/categories', [CategoryController::class, 'store']);

Route::put('/categories/{id}', [CategoryController::class, 'update']);

Route::get('/categories/parent/{parentId?}', [CategoryController::class, 'getByParent']);

Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

//PRODUCTOS
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
Route::get('/categories/{categoryId}/products', [ProductController::class, 'getByCategory']);

//AUTH
Route::post('/register', RegisterUserController::class);
Route::post('/login', LoginUserController::class); 
// Rutas protegidas, el middleware hace varias cosas
//al recibir la peticion con el ID y el token, va a la db y busca el ID 3
//y comprueba de que el token es válido y no ha expirado, si el token es valido
//busca el id del usuario asociado a ese tokeny y hace una consulta a la db
//para trar el model de Laravel de ese usuario, luego inyecta ese modelo
//de usuario dentro del objeto $request
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', GetProfileController::class);
});

Route::post('/verify-email', VerifyEmailController::class);
//Permite solo 3 peticiones por minuto para esta ruta
Route::post('/resend-code', ResendVerificationCodeController::class)->middleware('throttle:3,1');

//Rutas protegidas que requieren que el usuario envíe su token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', LogoutUserController::class);
    
    // Aquí también irán en el futuro las rutas como /api/perfil, /api/carrito, etc.
});


// Ruta para solicitar el código de recuperación (Máximo 3 intentos por minuto)
Route::post('/forgot-password', ForgotPasswordController::class)->middleware('throttle:3,1');

//Reuta para cambiar la contraseña xd
Route::post('/reset-password', ResetPasswordController::class)->middleware('throttle:3,1');

