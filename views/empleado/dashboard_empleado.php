<?php
session_start();

// Validar que exista la sesión y que el rol sea 2 (Empleado)
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 2) {
    header("Location: ../public/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Empleado - Cinex+</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #000; color: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn-logout { background: #d90000; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 14px;}
        .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Panel de Control - Empleado: <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
        <a href="../../auth/logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
    <div class="container">
        <h3>Módulo de Gestión</h3>
        <p>Próximamente aquí podrás validar entradas y gestionar la cartelera.</p>
        <!-- Aquí irá la lógica de empleados en futuras entregas -->
    </div>
</body>
</html>