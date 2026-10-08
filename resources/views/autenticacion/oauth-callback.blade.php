<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciando sesión...</title>
</head>
<body>
    <p style="font-family: sans-serif; text-align:center; margin-top:3rem;">
        Iniciando sesión, un momento...
    </p>
    <script>
        //El controlador nos mandó a: /oauth/callback#token=XXXXX
        //Leemos lo que viene después del '#'
        const hash = window.location.hash.substring(1); // quita el '#'
        const params = new URLSearchParams(hash);
        const token = params.get('token');

        if (token) {
            //Guardamos igual que el login normal
            localStorage.setItem('auth_token', token);
            //Reemplazamos el historial para que "atrás" no regrese aquí
            window.location.replace('/');
        } else {
            window.location.replace('/login?oauth_error=1');
        }
    </script>
</body>
</html>