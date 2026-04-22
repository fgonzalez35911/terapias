<?php
session_start();
require_once 'db.php';
require_once('fpdf/fpdf.php');

if (!isset($_SESSION['usuario_id'])) exit;

$t_id = $_POST['terapeuta_id'];
$tipo = $_POST['tipo_documento'];
$monto = ($tipo === 'Factura') ? $_POST['monto'] : null;

try {
    $sql = "INSERT INTO reintegros (usuario_id, terapeuta_id, tipo_documento, nombre_beneficiario, descripcion_servicio, cantidad_sesiones, fecha_emision, mes_correspondiente, anio_correspondiente, monto_total_facturado, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Firmado')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['usuario_id'], $t_id, $tipo, 
        $_POST['beneficiario'], $_POST['prestacion'], 
        $_POST['sesiones'] ?? null, $_POST['fecha'] ?? null, 
        $_POST['mes'], $_POST['anio'], $monto
    ]);
    
    $r_id = $pdo->lastInsertId();
    // Reparador de Base64 para que la imagen no se corrompa en Hostinger
    $data = base64_decode(str_replace(' ', '+', str_replace('data:image/png;base64,', '', $_POST['imagen_hd'])));
    $tmp = 'uploads/temp_'.$r_id.'.png';
    file_put_contents($tmp, $data);

    $pdf = new FPDF(); 
    $pdf->AddPage(); 
    $pdf->Image($tmp, 0, 0, 210, 297);
    $ruta = 'uploads/reintegro_'.$r_id.'.pdf';
    $pdf->Output('F', $ruta);
    unlink($tmp);

    $pdo->prepare("INSERT INTO archivos_adjuntos (reintegro_id, nombre_archivo, ruta_archivo, tipo_archivo, editado) VALUES (?, ?, ?, 'pdf', 1)")
        ->execute([$r_id, basename($ruta), $ruta]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) { 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
}
?>