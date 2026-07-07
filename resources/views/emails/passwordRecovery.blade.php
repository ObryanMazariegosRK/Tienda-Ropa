<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Hola, {{ $name }}</h2>
    <p>Has solicitado restablecer tu contraseña para tu cuenta en nuestra tienda.</p>
    <p>Tu código de recuperación es el siguiente:</p>
    <div style="background-color: #f3f4f6; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 2px; text-align: center; border-radius: 5px; margin: 20px 0; max-width: 200px;">
        {{ $code }}
    </div>
    <p>Este código expirará en 15 minutos. Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
    <br>
    <p>Saludos,<br>El equipo de la Tienda de Ropa</p>
</body>
</html>