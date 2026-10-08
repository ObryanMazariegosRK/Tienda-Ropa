<?php

namespace App\Http\Controllers\AuthController;

use App\Application\DTOs\User\SocialLoginDTO;
use App\Application\UseCases\User\HandleSocialLoginUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    //solo permitimos estos proveedores, nada más
    private const PROVIDERS_PERMITIDOS = ['google', 'facebook'];

    public function __construct(
        private HandleSocialLoginUseCase $handleSocialLogin
    ) {}

    //paso 2 mandamos al usuario a Google 
    public function redirect(string $provider)
    {
        //Si alguien intenta /auth/tiktok/redirect, lo cortamos con un 404
        abort_unless(in_array($provider, self::PROVIDERS_PERMITIDOS), 404);

        //Socialite arma la URL de Google y redirige el navegador allá
        return Socialite::driver($provider)->redirect();
    }

    //paso  4-6, Google nos regresa aquí 
    public function callback(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS_PERMITIDOS), 404);

        try {
            //Socialite toma el código que trae la URL, le pregunta a Google
            //¿quién es? y nos devuelve los datos del usuario ya listos
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            //Si Google rechaza, el usuario canceló, o el código venció
            Log::error("OAuth $provider falló: " . $e->getMessage());
            return redirect('/login?oauth_error=1');
        }

        //En caso de que google mande el nombre completo en un solo campo
        //Lo partimos en nombre + apellido para encajar con la entidad
        $nombreCompleto = $socialUser->getName() ?? $socialUser->getNickname() ?? 'Usuario';
        $partes = explode(' ', trim($nombreCompleto), 2);

        //Llenamos el DTO con lo que Google nos dio
        $dto = new SocialLoginDTO(
            provider:   $provider,
            providerId: $socialUser->getId(),
            email:      $socialUser->getEmail(),
            name:       $partes[0],
            lastName:   $partes[1] ?? '',
        );

        //Se la pasamos al use case, que resuelve las 3 situaciones y nos da el token
        $token = $this->handleSocialLogin->execute($dto);

        //Mandamos al usuario a una página que guardará el token en localStorage.
        //Usamos '#' (fragmento) para que el token NO viaje al servidor ni quede en logs.
        return redirect('/oauth/callback#token=' . $token);
    }
}