<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Abriendo pedido...</title>
</head>
<body>
    <p style="font-family: sans-serif; text-align:center; margin-top:3rem;">
        Abriendo tu pedido, un momento...
    </p>
    <script>
        
        const codigo = "{{ $codigo }}";
        const adminToken  = localStorage.getItem('admin_auth_token');
        const clientToken = localStorage.getItem('auth_token');

        if (adminToken) {
            //Es un admin (logueado en el panel) → al panel con el pedido abierto
            window.location.replace('/admin/pedidos?codigo=' + encodeURIComponent(codigo));
        } else if (clientToken) {
            //Es un cliente → a sus pedidos
            window.location.replace('/mis-pedidos?codigo=' + encodeURIComponent(codigo));
        } else {
            //Nadie con sesión → al login de la tienda
            window.location.replace('/login');
        }
    </script>
</body>
</html>