<!doctype html>
<html lang="es">
<head>
    <title>Cambiar Contraseña - Tienda de Ropa</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('Auth/css/style.css') }}">
    
    <style>
        /* Un pequeño estilo extra para que el código destaque */
        .input-code { font-size: 20px; letter-spacing: 5px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center mb-5">
                    <h2 class="heading-section">Seguridad</h2>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <div class="wrap d-md-flex">
                        
                        <div class="img" style="background-image: url({{asset('Auth/images/bg-1.jpg') }});"></div>
                        
                        <div class="login-wrap p-4 p-md-5">
                            <div class="d-flex">
                                <div class="w-100">
                                    <h3 class="mb-4">Crear nueva contraseña</h3>
                                </div>
                            </div>
                            
                            <form id="resetPasswordForm" class="signin-form">
                                <div class="form-group mb-3">
                                    <label class="label" for="email">Correo Electrónico</label>
                                    <input type="email" id="email" name="email" class="form-control text-muted" readonly required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="label" for="code">Código de Verificación (6 dígitos)</label>
                                    <input type="text" id="code" name="code" class="form-control input-code" maxlength="6" placeholder="------" required autocomplete="off">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="label" for="password">Nueva Contraseña</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="label" for="password_confirmation">Confirmar Contraseña</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repite la contraseña" required>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" id="submitBtn" class="form-control btn btn-primary rounded submit px-3">Actualizar Contraseña</button>
                                </div>
                                
                                <div id="errorMessage" class="alert d-none mt-3" role="alert"></div>
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
        
        // 1. Obtener el correo de la URL y asignarlo al input
        const urlParams = new URLSearchParams(window.location.search);
        const emailFromUrl = urlParams.get('email');
        if (emailFromUrl) {
            document.getElementById('email').value = emailFromUrl;
        }

        const resetPasswordForm = document.getElementById('resetPasswordForm');
        const errorMessage = document.getElementById('errorMessage');
        const submitBtn = document.getElementById('submitBtn');

        resetPasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Limpiamos la alerta
            errorMessage.classList.add('d-none');
            errorMessage.className = "alert d-none mt-3";
            submitBtn.disabled = true;
            submitBtn.textContent = 'Actualizando...';

            const email = document.getElementById('email').value;
            const code = document.getElementById('code').value;
            const password = document.getElementById('password').value;
            const password_confirmation = document.getElementById('password_confirmation').value;

            try {
                // Hacemos la petición a nuestra API
                const response = await fetch('/api/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        code: code,
                        password: password,
                        password_confirmation: password_confirmation
                    })
                });

                const data = await response.json();

                // Validación de errores de Laravel (ej. contraseñas que no coinciden)
                if (response.status === 422) {
                    const firstError = Object.values(data.errors)[0][0];
                    throw new Error(firstError);
                }

                if (!response.ok) {
                    throw new Error(data.message || 'Error al restablecer la contraseña.');
                }

                // Éxito: Mostrar mensaje verde y redirigir
                errorMessage.textContent = '¡Contraseña actualizada! Redirigiendo al Login...';
                errorMessage.className = "alert alert-success mt-3";
                errorMessage.classList.remove('d-none');

                setTimeout(() => { 
                    window.location.href = '/login'; 
                }, 2000);

            } catch (error) {
                // Error: Mostrar mensaje rojo
                errorMessage.textContent = error.message;
                errorMessage.className = "alert alert-danger mt-3";
                errorMessage.classList.remove('d-none');
            } finally {
                // Restauramos el botón
                submitBtn.disabled = false;
                if(!errorMessage.classList.contains('alert-success')){
                    submitBtn.textContent = 'Actualizar Contraseña';
                }
            }
        });
    });
    </script>
</body>
</html>