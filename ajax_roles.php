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
        $nombre_nuevo = trim($_POST['nombre_rol']);
        $permisos_json = $_POST['permisos_json'];

        if (empty($id)) {
            // CREAR NUEVO
            $stmt = $pdo->prepare("SELECT id FROM roles_dinamicos WHERE nombre_rol = ?");
            $stmt->execute([$nombre_nuevo]);
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Ese nombre de rol ya existe.']);
                exit;
            }
            $pdo->prepare("INSERT INTO roles_dinamicos (nombre_rol, permisos_json) VALUES (?, ?)")
                ->execute([$nombre_nuevo, $permisos_json]);
        } else {
            // EDITAR EXISTENTE
            // 1. Obtener nombre viejo
            $stmt = $pdo->prepare("SELECT nombre_rol FROM roles_dinamicos WHERE id = ?");
            $stmt->execute([$id]);
            $nombre_viejo = $stmt->fetchColumn();

            // 2. Actualizar tabla de roles
            $pdo->prepare("UPDATE roles_dinamicos SET nombre_rol = ?, permisos_json = ? WHERE id = ?")
                ->execute([$nombre_nuevo, $permisos_json, $id]);

            // 3. Si cambió el nombre, actualizar a los usuarios en cascada
            if ($nombre_viejo && $nombre_viejo !== $nombre_nuevo) {
                $pdo->prepare("UPDATE usuarios SET rol = ? WHERE rol = ?")
                    ->execute([$nombre_nuevo, $nombre_viejo]);
                
                // Actualizar sesión si es el rol propio
                if ($_SESSION['rol'] === $nombre_viejo) {
                    $_SESSION['rol'] = $nombre_nuevo;
                }
            }
        }
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'eliminar') {
        $pdo->prepare("DELETE FROM roles_dinamicos WHERE id = ?")->execute([$_POST['id']]);
        echo json_encode(['status' => 'success']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}