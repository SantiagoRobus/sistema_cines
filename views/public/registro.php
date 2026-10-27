<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Cines</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #2c2c2c; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; margin: 0; }
        .login-container { background-color: #000; color: #fff; padding: 30px; border-radius: 8px; width: 350px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .login-container h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-size: 14px; }
        .form-group input { padding: 10px; border-radius: 4px; border: 1px solid #555; background: #222; color: #fff; }
        .btn-login { background-color: #d90000; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px;}
        .links { margin-top: 15px; display: flex; justify-content: center; font-size: 12px; }
        .links a { color: #ccc; text-decoration: none; }
        .links a:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Regístrese con nosotros</h2>
        
        <form action="../../auth/register_process.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="codigo_secreto">Código de Empleado (Opcional):</label>
                <input type="password" id="codigo_secreto" name="codigo_secreto" placeholder="Solo personal autorizado">
            </div>
            
            <button type="submit" class="btn-login">Registrarse</button>
        </form>

        <div class="links">
            <a href="login.php">¿Ya tienes cuenta? Inicia sesión aquí</a>
        </div>
    </div>

    <script>
        // Capturar mensajes de la URL para mostrar notificaciones personalizadas
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');

        if (msg === 'success') {
            Swal.fire({
                title: '¡Registro Exitoso!',
                text: 'Tu cuenta ha sido creada. Ahora puedes iniciar sesión.',
                icon: 'success',
                confirmButtonColor: '#d90000'
            }).then(() => {
                window.location.href = 'login.php'; // Redirigir al login tras cerrar la alerta
            });
        } else if (msg === 'error') {
            Swal.fire({
                title: 'Error en el registro',
                text: 'Es posible que el correo electrónico ya esté en uso.',
                icon: 'error',
                confirmButtonColor: '#d90000'
            });
            // Limpiar la URL
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>