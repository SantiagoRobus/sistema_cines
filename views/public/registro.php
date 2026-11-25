<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body class="auth-page">
    <div class="login-container">
        <h2>Regístrese con nosotros</h2>
        
        <form action="../../auth/register_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
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
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                <small>Mínimo 8 caracteres: mayúscula, minúscula, número y símbolo (* ? ! @ # $ / ( ) { } = . , ; :). Sin espacios, patrones simples ni tu nombre o correo.</small>
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
        } else if (msg === 'invalid') {
            Swal.fire({
                title: 'Revisa tus datos',
                text: 'La contraseña debe tener 8 caracteres, mayúscula, minúscula, número y símbolo, sin espacios, patrones ni datos personales.',
                icon: 'warning',
                confirmButtonColor: '#f6bd4b'
            });
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>