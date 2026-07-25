<!doctype html>
<html lang="en">
  <head>
  	<title>Iniciar Sesión - Panel de Administración</title>
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
					<h2 class="heading-section">Panel de Administración</h2>
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
			      	        </div>

                            <form id="adminLoginForm" class="signin-form">
			      		        <div class="form-group mb-3">
			      			        <label class="label" for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="admin@ejemplo.com" required>
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
                                        <label class="checkbox-wrap checkbox-primary mb-0">Mantener sesión iniciada
                                            <input type="checkbox" id="rememberMe" name="remember_me" checked>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div class="w-50 text-md-right">
                                        <a href="/recuperar-password?origin=admin">¿Olvidaste tu contraseña?</a>
                                    </div>
                                </div>
		                    </form>
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
        const adminLoginForm = document.getElementById('adminLoginForm');
        const errorMessage = document.getElementById('errorMessage');
        const submitBtn = document.getElementById('submitBtn');

        adminLoginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            errorMessage.classList.add('d-none');
            errorMessage.textContent = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Ingresando...';

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                        remember_me: document.getElementById('rememberMe').checked
                    })
                });

                const data = await response.json();

                // Misma validación de correo no verificado que usa el cliente
                if (response.status === 403 && data.status === 'not_verified') {
                    errorMessage.textContent = 'Cuenta no verificada. Redirigiendo al formulario de validación...';
                    errorMessage.classList.remove('d-none');
                    errorMessage.className = "alert alert-warning mt-3";

                    const emailEncoded = encodeURIComponent(data.email);
                    setTimeout(() => {
                        window.location.href = `/verify?email=${emailEncoded}`;
                    }, 1000);
                    return;
                }

                if (!response.ok) {
                    throw new Error(data.error || data.message || 'Credenciales incorrectas.');
                }

                // Verificamos el rol ANTES de dar acceso al panel
                const profileResponse = await fetch('/api/profile', {
                    headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${data.token}` },
                    cache: 'no-store'
                });
                const profile = await profileResponse.json();

                if (!profileResponse.ok || profile.role !== 'admin') {
                    // No es admin: revocamos el token que se generó y rechazamos
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${data.token}` }
                    });
                    throw new Error('Esta cuenta no tiene permisos de administrador.');
                }

                localStorage.setItem('admin_auth_token', data.token);
                window.location.href = '/admin/dashboard';

            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ingresar';
            }
        });
    });
    </script>
	</body>
</html>