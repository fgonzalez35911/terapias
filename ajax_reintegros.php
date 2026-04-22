<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'eliminar') {
        if (!tienePermiso('eliminar_reintegros')) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para eliminar.']);
            exit;
        }
        
        $id = $_POST['id'];
        
        // Buscar el archivo asociado para borrar el PDF de tu carpeta física y no ocupar espacio basura
        $stmtA = $pdo->prepare("SELECT ruta_archivo FROM archivos_adjuntos WHERE reintegro_id = ?");
        $stmtA->execute([$id]);
        $archivo = $stmtA->fetch();
        
        if ($archivo && !empty($archivo['ruta_archivo']) && file_exists($archivo['ruta_archivo'])) {
            unlink($archivo['ruta_archivo']);
        }
        
        // Borrar el registro de la base de datos
        $pdo->prepare("DELETE FROM reintegros WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success']);

    } elseif ($action === 'cambiar_estado') {
        if (!tienePermiso('aprobar_reintegros')) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para cambiar estados.']);
            exit;
        }
        
        $id = $_POST['id'];
        $estado = $_POST['estado'];
        
        $pdo->prepare("UPDATE reintegros SET estado = ? WHERE id = ?")->execute([$estado, $id]);
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>