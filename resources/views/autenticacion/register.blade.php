<!doctype html>
<html lang="es">
  <head>
  	<title>Regístrate - Tienda de Ropa</title>
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
						
                        <div class="login-wrap p-4 p-md-5">
			      	        <div class="d-flex">
			      		        <div class="w-100">
			      			        <h3 class="mb-4">Crear una Cuenta</h3>
			      		        </div>
			      	        </div>
							
                            <form id="registerForm" class="signin-form">
                                <div class="form-group mb-3">
                                    <label class="label" for="name">Nombre</label>
                                    <input type="text" id="name" class="form-control" placeholder="Tu nombre" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="label" for="last_name">Apellido</label>
                                    <input type="text" id="last_name" class="form-control" placeholder="Tu apellido" required>
                                </div>
			      		        <div class="form-group mb-3">
			      			        <label class="label" for="email">Correo Electrónico</label>
			      			        <input type="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
			      		        </div>
                                <div class="form-group mb-3">
                                    <label class="label" for="phone">Teléfono</label>
                                    <input type="text" id="phone" class="form-control" placeholder="Ej: 5559876543" required>
                                </div>
		                        <div class="form-group mb-3">
		            	            <label class="label" for="password">Contraseña</label>
		                            <input type="password" id="password" class="form-control" placeholder="Tu contraseña" required>
		                        </div>
		                        
                                <div class="form-group">
		            	            <button type="submit" id="submitBtn" class="form-control btn btn-primary rounded submit px-3">Registrarme</button>
		                        </div>
                                
                                <div id="errorMessage" class="alert alert-danger d-none mt-3" role="alert"></div>
                                <div id="successMessage" class="alert alert-success d-none mt-3" role="alert"></div>
		                    </form>
                            
		                    <p class="text-center">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia Sesión</a></p>
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
        const registerForm = document.getElementById('registerForm');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        const submitBtn = document.getElementById('submitBtn');

        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Limpiamos mensajes previos
            errorMessage.classList.add('d-none');
            successMessage.classList.add('d-none');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creando cuenta...';

            // Armamos el JSON con los datos exactos que pide tu API
            const payload = {
                name: document.getElementById('name').value,
                last_name: document.getElementById('last_name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                phone: document.getElementById('phone').value
            };

            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    // Si el correo ya existe u otro error
                    throw new Error(data.message || 'Ocurrió un error al registrarte.');
                }

                // ¡Éxito!
                successMessage.textContent = '¡Cuenta creada con éxito! Redirigiendo...';
                successMessage.classList.remove('d-none');

                // Si tu API de registro también devuelve un token, lo guardamos e iniciamos sesión automáticamente:
                if(data.token) {
                    localStorage.setItem('auth_token', data.token);
                    setTimeout(() => { window.location.href = '/'; }, 1000);
                } else {
                    // Si solo registra pero no loguea, lo mandamos al login
                    const emailEncoded = encodeURIComponent(payload.email);
                    setTimeout(() => { window.location.href = `/verify?email=${emailEncoded}`; }, 1000);
                }

            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Registrarme';
            }
        });
    });
    </script>
	</body>
</html>