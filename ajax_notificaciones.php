<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) { exit(); }
$id_usuario = $_SESSION['usuario_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action == 'check') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
        $stmt->execute([$id_usuario]);
        $total = $stmt->fetchColumn();

        $stmt2 = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 AND notificada_en_vivo = 0");
        $stmt2->execute([$id_usuario]);
        $nuevas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($nuevas) > 0) {
            $pdo->prepare("UPDATE notificaciones SET notificada_en_vivo = 1 WHERE usuario_id = ? AND leida = 0")->execute([$id_usuario]);
        }
        echo json_encode(['total_sin_leer' => $total, 'nuevas_en_vivo' => $nuevas]);

    } elseif ($action == 'listado_completo') {
        $stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 50");
        $stmt->execute([$id_usuario]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($action == 'marcar_leida') {
        $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?")->execute([$_GET['id'], $id_usuario]);

    } elseif ($action == 'borrar') {
        $pdo->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?")->execute([$_GET['id'], $id_usuario]);

    } elseif ($action == 'limpiar_todo') {
        $pdo->prepare("DELETE FROM notificaciones WHERE usuario_id = ?")->execute([$id_usuario]);

    } elseif ($action == 'guardar_config_alertas') {
        // Borramos config vieja y guardamos la nueva por cada evento
        foreach ($_POST as $evento => $usuarios_array) {
            if ($evento == 'action') continue;
            $ids = implode(',', $usuarios_array);
            $pdo->prepare("INSERT INTO config_notificaciones (evento, usuarios_destino) VALUES (?, ?) ON DUPLICATE KEY UPDATE usuarios_destino = ?")
                ->execute([$evento, $ids, $ids]);
        }
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }