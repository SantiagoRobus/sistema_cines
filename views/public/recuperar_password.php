<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Cines</title>
    <!-- Librería SweetAlert2 para notificaciones personalizadas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Mantenemos la consistencia visual con el fondo gris #2c2c2c */
        body { 
            background-color: #2c2c2c; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            font-family: sans-serif; 
            margin: 0; 
        }
        .login-container { 
            background-color: #000; 
            color: #fff; 
            padding: 30px; 
            border-radius: 8px; 
            width: 350px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.3); 
        }
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
        <h2>Recuperar Contraseña</h2>
        
        <form action="../../auth/reset_password_process.php" method="POST">
            <div class="form-group">
                <label for="email">Correo Electrónico Registrado:</label>
                <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
            </div>
            
            <div class="form-group">
                <label for="password">Nueva Contraseña:</label>
                <input type="password" id="password" name="password" required placeholder="********">
            </div>
            
            <button type="submit" class="btn-login">Restablecer Contraseña</button>
        </form>

        <div class="links">
            <a href="login.php">¿Recordaste tu contraseña? Inicia sesión aquí</a>
        </div>
    </div>

    <script>
        // Sistema de alertas para la respuesta del servidor
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');

        if (msg === 'success') {
            Swal.fire({
                title: '¡Contraseña Actualizada!',
                text: 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión con tu nueva clave.',
                icon: 'success',
                confirmButtonColor: '#d90000'
            }).then(() => {
                window.location.href = 'login.php';
            });
        } else if (msg === 'not_found') {
            Swal.fire({
                title: 'Correo no registrado',
                text: 'El correo electrónico ingresado no se encuentra en nuestra base de datos.',
                icon: 'error',
                confirmButtonColor: '#d90000'
            });
            window.history.replaceState(null, null, window.location.pathname);
        } else if (msg === 'error') {
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un problema al intentar actualizar la contraseña. Inténtalo de nuevo.',
                icon: 'error',
                confirmButtonColor: '#d90000'
            });
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>