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

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // Nota: Como recién creamos la base, vamos a dejar que el usuario "federico" entre con la clave "admin123" por primera vez.
    if ($user && $usuario === 'federico' && $password === 'admin123') {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre_completo'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
    }
}
?>
