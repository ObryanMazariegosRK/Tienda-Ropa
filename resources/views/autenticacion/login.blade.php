<!doctype html>
<html lang="es">
  <head>
  	<title>Iniciar sesión - Amerishop</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	
	<link rel="stylesheet" href="{{ asset('Auth/css/style.css') }}">
	</head>
	<body>
	<section class="ftco-section">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-12 col-lg-10">
					<div class="wrap d-md-flex">

                        <div class="img" style="background-image: url({{ asset('Auth/images/bg-1.jpg') }});"></div>

                        <div class="login-wrap">

                            <h3>Iniciar sesión</h3>

                            <form id="loginForm" class="signin-form">
                                <div class="form-group">
                                    <label class="label" for="email">Correo electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="tucorreo@ejemplo.com" autocomplete="email" required>
                                </div>
                                <div class="form-group">
                                    <label class="label" for="password">Contraseña</label>
                                    <div class="password-field">
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Tu contraseña" autocomplete="current-password" required>
                                        <button type="button" class="toggle-password" data-target="password" aria-label="Mostrar contraseña"><span class="fa fa-eye"></span></button>
                                    </div>
                                </div>

                                <div class="form-group d-md-flex">
                                    <label class="checkbox-wrap checkbox-primary mb-0">Recordarme 30 días
                                        <input type="checkbox" name="remember_me" checked>
                                        <span class="checkmark"></span>
                                    </label>
                                    <a href="/recuperar-password">¿Olvidaste tu contraseña?</a>
                                </div>

                                <div id="errorMessage" class="alert alert-danger d-none" role="alert"></div>

                                <div class="form-group form-submit">
                                    <button type="submit" id="submitBtn" class="form-control btn btn-primary">Ingresar</button>
                                </div>
                            </form>

                            <div class="oauth-divider"><span>o continúa con</span></div>

                            <div class="oauth-buttons">
                                <a href="/auth/google/redirect" class="btn-oauth btn-oauth-google">
                                    <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                    </svg>
                                    <span>Continuar con Google</span>
                                </a>
                            </div>

                            <p class="text-center">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</section>

    <script src="{{ asset('Auth/js/jquery.min.js') }}"></script>
    <script src="{{ asset('Auth/js/popper.js') }}"></script>
    <script src="{{ asset('Auth/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('Auth/js/main.js') }}"></script>

    <script>

        
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');

        // Si venimos de un login social fallido, lo mostramos en el mismo recuadro de errores
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('oauth_error')) {
            errorMessage.textContent = 'No se pudo iniciar sesión con Google. Intenta de nuevo.';
            errorMessage.classList.remove('d-none');
        }
        const submitBtn = document.getElementById('submitBtn');


        

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Evitamos que la página se recargue

            //Limpiamos errores previos y bloqueamos el botón
            errorMessage.classList.add('d-none');
            errorMessage.textContent = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Ingresando...';

            //Obtenemos los datos del formulario
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            // NUEVO: Capturamos si el checkbox está marcado
            const rememberMe = document.querySelector('input[name="remember_me"]').checked;

            try {
                //Hacemos la petición a nuestra API de Laravel
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        remember_me: rememberMe
                    })
                });

                const data = await response.json();
                //Validamos si la cuenta existe pero no está verificada (Código 403)
                if (response.status === 403 && data.status === 'not_verified') {
                    errorMessage.textContent = 'Cuenta no verificada. Redirigiendo al formulario de validación...';
                    errorMessage.classList.remove('d-none');
                    errorMessage.className = "alert alert-warning mt-3"; // Un color amarillo de advertencia

                    // Redirigimos automáticamente pasándole el correo a la vista de verificación
                    const emailEncoded = encodeURIComponent(data.email);
                    setTimeout(() => { 
                        window.location.href = `/verify?email=${emailEncoded}`; 
                    }, 1000);
                    return; // Detenemos la ejecución aquí
                }

                //Revisamos si el backend nos devolvió algún error 
                if (!response.ok) {
                    throw new Error(data.error || data.message || 'Ocurrió un error al iniciar sesión.');
                }

                //Guardamos el token en el navegador (Local Storage)
                localStorage.setItem('auth_token', data.token);  

                //Redirigimos al usuario a la página principal de la tienda
                window.location.href = '/'; 

            } catch (error) {
                //Si algo falla, mostramos el cuadro rojo de error que creamos en el HTML
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('d-none');
            } finally {
                //Restauramos el botón
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ingresar';
            }
        });
    });
    </script>
	</body>
</html>