<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

if (!isset($_SESSION['usuario_id']) || !tienePermiso('configuracion_sistema') || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'guardar') {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $rol = $_POST['rol'];
        
        // Recepción de los nuevos datos del perfil
        $dni = trim($_POST['dni'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $genero = $_POST['genero'] ?? 'otro';
        $fecha_nac = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;

        if (empty($id)) {
            // INSERTAR NUEVO USUARIO
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'El nombre de usuario ya existe.']);
                exit;
            }
            
            // Limpiamos la clave (trim) por si se copió con un espacio al final
            $pass_limpia = trim($_POST['pass']);
            $pass_hash = password_hash($pass_limpia, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios (usuario, password, nombre_completo, rol, dni, email, telefono, genero, fecha_nacimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$usuario, $pass_hash, $nombre, $rol, $dni, $email, $telefono, $genero, $fecha_nac]);
            
        } else {
            // EDITAR USUARIO EXISTENTE
            $sql = "UPDATE usuarios SET nombre_completo = ?, rol = ?, dni = ?, email = ?, telefono = ?, genero = ?, fecha_nacimiento = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$nombre, $rol, $dni, $email, $telefono, $genero, $fecha_nac, $id]);
        }
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'reset_pass') {
        // Limpieza de espacios accidentales
        $pass_limpia = trim($_POST['pass']);
        $pass_hash = password_hash($pass_limpia, PASSWORD_DEFAULT);
        
        $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$pass_hash, $_POST['id']]);
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'eliminar') {
        if ($_POST['id'] == $_SESSION['usuario_id']) {
            echo json_encode(['status' => 'error', 'message' => 'No puedes eliminarte a ti mismo.']);
            exit;
        }
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$_POST['id']]);
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>