<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$id = $_SESSION['usuario_id'];

try {
    if ($accion === 'actualizar_datos') {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, dni = ? WHERE id = ?");
        $stmt->execute([$_POST['nombre'], $_POST['dni'], $id]);
        echo json_encode(['status' => 'success']);
    } elseif ($accion === 'guardar_firma') {
        $stmt = $pdo->prepare("UPDATE usuarios SET firma_digital = ? WHERE id = ?");
        $stmt->execute([$_POST['firma_b64'], $id]);
        echo json_encode(['status' => 'success']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
