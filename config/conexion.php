<?php
// config/conexion.php
$host = "localhost";
$dbname = "sistema_cines";
$user = "postgres";
$password = "Ds0310**"; // Tu contraseña actual

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
} 
?>