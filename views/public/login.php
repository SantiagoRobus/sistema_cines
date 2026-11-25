<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Cinex+</title>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body class="auth-page">
    <div class="login-container">
        <h2>Inicio de Sesion</h2>
    
        <!-- Mostrar mensajes de error si las credenciales fallan -->
        <?php if(isset($_GET['error'])): ?>
            <div class="error-msg">Credenciales incorrectas o usuario inactivo.</div>
        <?php endif; ?>

        <!-- El formulario envía los datos a login_process.php mediante el método POST -->
        <form action="../../auth/login_process.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn-login">Iniciar sesión</button>
    </form>

    <div class="links">
        <a href="registro.php">Registrese con nosotros</a>
        <a href="recuperar_password.php">Olvidé mi contraseña</a>
    </div>
    </div>
</body>
</html>