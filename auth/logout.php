<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir también la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
// Redirigir al login
header("Location: ../views/public/login.php");
exit();
?>