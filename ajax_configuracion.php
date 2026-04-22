<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    if ($accion === 'perfil') {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, dni = ? WHERE id = ?");
        $stmt->execute([$_POST['nombre'], $_POST['dni'], $_SESSION['usuario_id']]);
        echo json_encode(['status' => 'success']);

    } elseif ($accion === 'smtp') {
        $stmt = $pdo->prepare("UPDATE configuracion SET smtp_host = ?, smtp_port = ?, smtp_user = ?, smtp_pass = ?, email_reintegros = ? WHERE id = 1");
        $stmt->execute([$_POST['host'], $_POST['port'], $_POST['user'], $_POST['pass'], $_POST['destino']]);
        echo json_encode(['status' => 'success']);

    } elseif ($accion === 'firma') {
        // Guardamos la firma en Base64 directamente en la tabla usuarios
        $stmt = $pdo->prepare("UPDATE usuarios SET firma_digital = ? WHERE id = ?");
        $stmt->execute([$_POST['firma_b64'], $_SESSION['usuario_id']]);
        echo json_encode(['status' => 'success']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
