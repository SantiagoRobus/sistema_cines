<?php
session_start();
// Destruir todas las variables de sesión
$_SESSION = array();
// Destruir la sesión final
session_destroy();
// Redirigir al login
header("Location: ../views/public/login.php");
exit();
?>