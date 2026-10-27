<?php
session_start();

// Validar que exista la sesión y que el rol sea 3 (Cliente)
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 3) {
    header("Location: ../public/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Cliente - Cinex+</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #000; color: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn-logout { background: #d90000; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 14px;}
        .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
        <a href="../../auth/logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
    <div class="container">
        <h3>¿En qué podemos ayudarte hoy?</h3>
        <p>Próximamente aquí podrás ver la cartelera y hacer tus reservas.</p>
        <!-- Aquí irá el CRUD de reservas o visualización de películas en el futuro -->
    </div>
</body>
</html>