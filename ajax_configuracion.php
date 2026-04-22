<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

if (!tienePermiso('configuracion_sistema')) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'template') {
        $stmt = $pdo->prepare("UPDATE configuracion SET asunto_predeterminado = ?, cuerpo_email_base = ? WHERE id = 1");
        $stmt->execute([$_POST['asunto'], $_POST['cuerpo']]);
        echo json_encode(['status' => 'success']);

    } elseif ($accion === 'smtp') {
        $stmt = $pdo->prepare("UPDATE configuracion SET smtp_host = ?, smtp_user = ?, smtp_pass = ?, email_reintegros = ? WHERE id = 1");
        $stmt->execute([$_POST['host'], $_POST['user'], $_POST['pass'], $_POST['destino']]);
        echo json_encode(['status' => 'success']);

    } elseif ($accion === 'guardar_config_alertas') {
        foreach ($_POST as $evento => $usuarios_array) {
            if ($evento == 'accion') continue;
            $ids = implode(',', $usuarios_array);
            $pdo->prepare("INSERT INTO config_notificaciones (evento, usuarios_destino) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuarios_destino = ?")
                ->execute([$evento, $ids, $ids]);
        }
        echo json_encode(['status' => 'success']);

    } elseif ($accion === 'limpiar_datos') {
        // Lógica de limpieza que ya teníamos...
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE archivos_adjuntos; TRUNCATE TABLE reintegros; SET FOREIGN_KEY_CHECKS = 1;");
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}