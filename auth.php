<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completá todos los campos.']);
        exit;
    }

    // Limpiamos espacios y buscamos por usuario o email con contraseña encriptada
    $u_limpio = trim($usuario);
    $p_limpia = trim($password);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? OR email = ?");
    $stmt->execute([$u_limpio, $u_limpio]);
    $user = $stmt->fetch();

    $pass_correcta = password_verify($p_limpia, $user['password']);
    
    // Salvavidas para que recuperes tu acceso YA MISMO
    if ($user['usuario'] === 'federico' && $p_limpia === 'admin123') { 
        $pass_correcta = true; 
    }

    if ($user && $pass_correcta) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre_completo'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
    }
}
?>
