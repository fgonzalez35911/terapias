<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

$id = $_POST['id'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$especialidad = $_POST['especialidad'] ?? '';
$cuit = $_POST['cuit'] ?? '';
$valor_sesion = $_POST['valor_sesion'] ?? 0;

if (empty($nombre)) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
    exit;
}

try {
    if (empty($id)) {
        // Es uno nuevo, hacemos INSERT
        $stmt = $pdo->prepare("INSERT INTO terapeutas (nombre, especialidad, cuit, valor_sesion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $especialidad, $cuit, $valor_sesion]);
    } else {
        // Ya existe, hacemos UPDATE
        $stmt = $pdo->prepare("UPDATE terapeutas SET nombre = ?, especialidad = ?, cuit = ?, valor_sesion = ? WHERE id = ?");
        $stmt->execute([$nombre, $especialidad, $cuit, $valor_sesion, $id]);
    }
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
