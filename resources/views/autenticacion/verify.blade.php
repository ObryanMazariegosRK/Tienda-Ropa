<!doctype html>
<html lang="es">
  <head>
  	<title>Verificar Cuenta - Tienda de Ropa</title>
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
			      			        <h3 class="mb-3">Verifica tu Cuenta</h3>
                                    <p class="text-muted small">Hemos enviado un código de 6 dígitos a tu correo electrónico. Es válido por 15 minutos.</p>
			      		        </div>
			      	        </div>
							
                            <form id="verifyForm" class="signin-form">
                                <div class="form-group mb-3">
                                    <label class="label" for="email">Correo Electrónico</label>
                                    <input type="email" id="email" class="form-control" readonly required style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>

                                <div class="form-group mb-3">
			      			        <label class="label" for="code">Código de Verificación</label>
			      			        <input type="text" id="code" class="form-control text-center" placeholder="000000" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required style="font-size: 20px; letter-spacing: 4px; font-weight: bold;">
			      		        </div>
                                
                                <div class="form-group">
		            	            <button type="submit" id="submitBtn" class="form-control btn btn-primary rounded submit px-3">Validar Código</button>
		                        </div>
                                
                                <div id="errorMessage" class="alert alert-danger d-none mt-3" role="alert"></div>
                                <div id="successMessage" class="alert alert-success d-none mt-3" role="alert"></div>
		                    </form>
                            
                            <p class="text-center mt-4">
                                ¿No recibiste el código o ya caducó?<br>
                                <a href="#" id="resendBtn" class="font-weight-bold">Reenviar nuevo código</a>
                            </p>
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
        const verifyForm = document.getElementById('verifyForm');
        const emailInput = document.getElementById('email');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        const submitBtn = document.getElementById('submitBtn');
        const resendBtn = document.getElementById('resendBtn');

        // 1. Auto-capturar el correo desde la URL (ej: /verify?email=maza@gmail.com)
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email');
        
        if (emailParam) {
            emailInput.value = emailParam;
        } else {
            // Si por alguna razón entraron a /verify directamente sin correo, los mandamos al login
            window.location.href = '/login';
        }

        // ==========================================
        // ACCIÓN A: ENVIAR EL CÓDIGO DE VALIDACIÓN
        // ==========================================
        verifyForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            errorMessage.classList.add('d-none');
            successMessage.classList.add('d-none');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verificando...';

            try {
                // Ajusta esta URL según tu ruta de API para validar
                const response = await fetch('/api/verify-email', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: emailInput.value,
                        code: document.getElementById('code').value
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Código incorrecto o expirado.');
                }

                // Si la validación es correcta y te devuelve el token de sesión:
                successMessage.textContent = '¡Cuenta verificada con éxito! Iniciando sesión...';
                successMessage.classList.remove('d-none');

                if (data.token) {
                    localStorage.setItem('auth_token', data.token);
                }

                // Redirigir a la tienda principal después de 1.5 segundos
                setTimeout(() => { window.location.href = '/'; }, 1000);

            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Validar Código';
            }
        });

        // ==========================================
        // ACCIÓN B: REENVIAR NUEVO CÓDIGO
        // ==========================================
        resendBtn.addEventListener('click', async function(e) {
            e.preventDefault();

            errorMessage.classList.add('d-none');
            successMessage.classList.add('d-none');
            
            // Bloqueamos temporalmente el enlace para evitar múltiples clics continuos
            resendBtn.style.pointerEvents = 'none';
            resendBtn.style.opacity = '0.5';
            resendBtn.textContent = 'Enviando código nuevo...';

            try {
                // Ajusta esta URL según tu ruta de API para reenviar (ej: /api/resend-code)
                const response = await fetch('/api/resend-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: emailInput.value
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'No se pudo reenviar el código.');
                }

                successMessage.textContent = '¡Se ha enviado un código nuevo a tu correo!';
                successMessage.classList.remove('d-none');

            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('d-none');
            } finally {
                // Reactivamos el botón tras terminar la petición
                resendBtn.style.pointerEvents = 'auto';
                resendBtn.style.opacity = '1';
                resendBtn.textContent = 'Reenviar nuevo código';
            }
        });
    });
    </script>
	</body>
</html>