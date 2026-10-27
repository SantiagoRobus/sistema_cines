<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesiónc - Cinex+</title>
    <style>
        body{background-color: #2c2c2c; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
        .login-container {background-color: #000; color: #fff; padding: 30px; border-radius: 8px; width: 350px; box-shadow: 0 4px 8px rgba (0,0,0,0.2); }
        .login-container h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-size: 14px; }
        .form-group input { padding: 10px; border-radius: 4px; border: 1px solid #555; background: #222; color: #fff; }
        .btn-login { background-color: #d90000; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px;}
        .links { margin-top: 15px; display: flex; justify-content: space-between; font-size: 12px; }
        .links a { color: #ccc; text-decoration: none; }
        .error-msg { background-color: #ffcccc; color: #cc0000; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center;}
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Inicio de Sesion</h2>
    
        <!-- Mostrar mensajes de error si las credenciales fallan -->
        <?php if(isset($_GET['error'])): ?>
            <div class="error-msg">Credenciales incorrectas o usuario inactivo.</div>
        <?php endif; ?>

        <!-- El formulario envía los datos a login_process.php mediante el método POST -->
        <form action="../../auth/login_process.php" method="POST">
        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn-login">Login</button>
    </form>

    <div class="links">
        <a href="registro.php">Registrese con nosotros</a>
        <a href="recuperar_password.php">Olvidé mi contraseña</a>
    </div>
    </div>
</body>
</html>