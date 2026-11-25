<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
$token = $_GET['token'] ?? '';
$tokenValido = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body class="auth-page">
    <main class="login-container">
        <h1><?php echo $tokenValido ? 'Crea una nueva contraseña' : 'Recupera tu cuenta'; ?></h1>

        <?php if ($tokenValido): ?>
            <form action="../../auth/reset_password_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                    <small>Usa 8 caracteres como mínimo con mayúscula, minúscula, número y símbolo. Sin espacios, patrones simples ni datos personales.</small>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirma tu contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn-login">Actualizar contraseña</button>
            </form>
        <?php else: ?>
            <p>Te enviaremos un enlace de un solo uso si encontramos una cuenta activa con ese correo.</p>
            <form action="../../auth/reset_password_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" autocomplete="email" required placeholder="tu@correo.com">
                </div>
                <button type="submit" class="btn-login">Enviar enlace de recuperación</button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">Volver a iniciar sesión</a>
        </div>
    </main>

    <script>
        const messages = {
            requested: ['Solicitud recibida', 'Si el correo está registrado y el envío de correo no está configurado, administración deberá verificar tu identidad para generar un enlace.', 'success'],
            success: ['Contraseña actualizada', 'Ya puedes iniciar sesión con tu nueva contraseña.', 'success'],
            invalid: ['Enlace o contraseña inválidos', 'El enlace pudo vencer. Solicita uno nuevo y revisa los requisitos de contraseña.', 'warning'],
            error: ['No se pudo completar la solicitud', 'Inténtalo de nuevo más tarde.', 'error']
        };
        const message = new URLSearchParams(window.location.search).get('msg');
        if (messages[message]) {
            Swal.fire({
                title: messages[message][0],
                text: messages[message][1],
                icon: messages[message][2],
                confirmButtonColor: '#f6bd4b'
            });
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>
</body>
</html>
