<!doctype html>
<html lang="en">
  <head>
  	<title>Iniciar Sesión - Tienda de Ropa</title>
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
				<div class="col-md-6 text-center mb-5">
					<h2 class="heading-section">Bienvenido</h2>
				</div>
			</div>
			<div class="row justify-content-center">
				<div class="col-md-12 col-lg-10">
					<div class="wrap d-md-flex">
						
                        <div class="img" style="background-image: url({{asset('Auth/images/bg-1.jpg') }});">
			            </div>
						
                        <div class="login-wrap p-4 p-md-5">
			      	        <div class="d-flex">
			      		        <div class="w-100">
			      			        <h3 class="mb-4">Iniciar Sesión</h3>
			      		        </div>
                                <!--
								<div class="w-100">
									<p class="social-media d-flex justify-content-end">
										<a href="#" class="social-icon d-flex align-items-center justify-content-center"><span class="fa fa-facebook"></span></a>
										<a href="#" class="social-icon d-flex align-items-center justify-content-center"><span class="fa fa-twitter"></span></a>
									</p>
								</div>-->
			      	        </div>
							
                            <form id="loginForm" class="signin-form">
			      		        <div class="form-group mb-3">
			      			        <label class="label" for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
			      		        </div>
		                        <div class="form-group mb-3">
		            	            <label class="label" for="password">Contraseña</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Tu contraseña" required>
		                        </div>
		                        <div class="form-group">
                                    <button type="submit" id="submitBtn" class="form-control btn btn-primary rounded submit px-3">Ingresar</button>
		                        </div>
                                
                                <div id="errorMessage" class="alert alert-danger d-none mt-3" role="alert"></div>

		                        <div class="form-group d-md-flex">
                                    <div class="w-50 text-left">
                                        <label class="checkbox-wrap checkbox-primary mb-0">Recordarme
                                            <input type="checkbox" name="remember_me" checked>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div class="w-50 text-md-right">
                                        <a href="/recuperar-password">¿Olvidaste tu contraseña?</a>
                                    </div>
                                </div>
		                    </form>
                            
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