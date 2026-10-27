<?php
session_start();
require_once '../config/conexion.php'; //llamamos a la conexión

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email']);
    $password = trim ($_POST['password']);
    
    try{
        //buscamos al usuario por su email
        $stmt = $pdo->prepare("SELECT cod_usuario, nombre, password_hash, cod_rol FROM USUARIO WHERE email = :email AND activo = '1'");
        $stmt->execute([':email' => $email]); 
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // se verifica que el usuario exista y que la contraseña coincida con el hash
        if ($usuario && password_verify($password, $usuario['password_hash'])){
            //guardamos los datos en la sesion
            $_SESSION['usuario_id'] = $usuario['cod_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol_id'] = $usuario['cod_rol'];
            
            if ($usuario['cod_rol'] == 1) {
                header("Location: ../views/admin/dashboard_admin.php");
            } elseif ($usuario['cod_rol'] == 2) {
                header("Location: ../views/empleado/dashboard_empleado.php");
            }else{
                header ("Location: ../views/cliente/dashboard_cliente.php");
            }
            exit();
        } else {
            // si falla vuelve al login con un error
            header("Location: ../views/public/login.php?error=1");
            exit();
        }
    } catch (PDOException $e) {
        die ("Error en la consulta: " . $e->getMessage());
    }
} else {
    header ("Location: ../views/public/login.php");
}
?>