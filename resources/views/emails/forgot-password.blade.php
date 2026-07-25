<!doctype html>
<html lang="es">
<head>
    <title>Recuperar Contraseña - Tienda de Ropa</title>
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
                    <h2 class="heading-section">Recuperar Acceso</h2>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <div class="wrap d-md-flex">
                        
                        <div class="img" style="background-image: url({{asset('Auth/images/bg-1.jpg') }});"></div>
                        
                        <div class="login-wrap p-4 p-md-5">
                            <div class="d-flex">
                                <div class="w-100">
                                    <h3 class="mb-4">¿Olvidaste tu contraseña?</h3>
                                    <p class="mb-4 text-muted">Ingresa tu correo y te enviaremos un código de 6 dígitos para recuperar tu cuenta.</p>
                                </div>
                            </div>
                            
                            <form id="forgotPasswordForm" class="signin-form">
                                <div class="form-group mb-3">
                                    <label class="label" for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" id="submitBtn" class="form-control btn btn-primary rounded submit px-3">Enviar Código</button>
                                </div>
                                
                                <div id="errorMessage" class="alert d-none mt-3" role="alert"></div>
                            </form>
                            
                            <p class="text-center mt-4" id="backToLoginWrap">
                                <a href="/login" id="backToLoginLink"><span class="fa fa-arrow-left"></span> Volver al inicio de sesión</a>
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
        // Detectamos si venimos del panel de admin, y lo recordamos
        // por si se pierde el parámetro en algún salto de página
        const urlParams = new URLSearchParams(window.location.search);
        const origin = urlParams.get('origin') || sessionStorage.getItem('password_reset_origin') || '';
        if (origin) {
            sessionStorage.setItem('password_reset_origin', origin);
        }
        document.getElementById('backToLoginLink').href = origin === 'admin' ? '/admin/login' : '/login';
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const errorMessage = document.getElementById('errorMessage');
        const submitBtn = document.getElementById('submitBtn');

        forgotPasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            errorMessage.classList.add('d-none');
            errorMessage.className = "alert d-none mt-3";
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';

            const email = document.getElementById('email').value;

            try {
                const response = await fetch('/api/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Ocurrió un error al enviar el código.');
                }

                // Mostrar mensaje de éxito
                errorMessage.textContent = '¡Código enviado! Redirigiendo...';
                errorMessage.className = "alert alert-success mt-3";
                errorMessage.classList.remove('d-none');

                // Redirigir a la vista de cambiar contraseña, pasando el correo por la URL
                setTimeout(() => {
                    const originParam = origin ? `&origin=${origin}` : '';
                    window.location.href = `/cambiar-password?email=${encodeURIComponent(email)}${originParam}`;
                }, 1500);
            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.className = "alert alert-danger mt-3";
                errorMessage.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
                if(!errorMessage.classList.contains('alert-success')){
                    submitBtn.textContent = 'Enviar Código';
                }
            }
        });
    });
    </script>
</body>
</html>