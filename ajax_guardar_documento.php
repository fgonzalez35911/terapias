<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
    exit;
}

$t_id = $_POST['terapeuta_id'];
$tipo = $_POST['tipo_documento'];
$monto = ($tipo === 'Factura') ? $_POST['monto'] : null;

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO reintegros (usuario_id, terapeuta_id, tipo_documento, nombre_beneficiario, descripcion_servicio, cantidad_sesiones, fecha_emision, mes_correspondiente, anio_correspondiente, monto_total_facturado, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Firmado')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['usuario_id'], $t_id, $tipo, 
        $_POST['beneficiario'], $_POST['prestacion'], 
        $_POST['sesiones'] ?? null, $_POST['fecha'] ?? null, 
        $_POST['mes'], $_POST['anio'], $monto
    ]);
    
    $r_id = $pdo->lastInsertId();
    $nombre_archivo = "reintegro_" . $r_id . "_" . time() . ".pdf";
    $ruta_destino = 'uploads/' . $nombre_archivo;

    // LÓGICA DE GUARDADO SEGÚN EL FORMATO RECIBIDO
    if (isset($_FILES['pdf_final'])) {
        // Guardamos el PDF original inyectado que viene de pdf-lib
        move_uploaded_file($_FILES['pdf_final']['tmp_name'], $ruta_destino);
    } elseif (isset($_POST['imagen_hd'])) {
        // PLAN B: Captura HD ajustada a hoja A4 real (210mm)
        require_once('fpdf/fpdf.php');
        $data = base64_decode(str_replace(' ', '+', str_replace('data:image/png;base64,', '', $_POST['imagen_hd'])));
        $tmp_img = 'uploads/temp_'.$r_id.'.png';
        file_put_contents($tmp_img, $data);
        
        // Obtenemos el tamaño en píxeles para calcular la proporción exacta
        list($ancho_px, $alto_px) = getimagesize($tmp_img);
        $proporcion = $alto_px / $ancho_px;
        
        // Forzamos el ancho a 210mm (A4 estándar) y calculamos el alto proporcional
        $ancho_final = 210;
        $alto_final = 210 * $proporcion;

        // Creamos el PDF en tamaño A4 estándar
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        
        // Estampamos la imagen ocupando el ancho total de la hoja (210mm)
        // El alto se ajusta solo para que no haya espacios raros entre letras
        $pdf->Image($tmp_img, 0, 0, $ancho_final, $alto_final);
        
        $pdf->Output('F', $ruta_destino);
        if (file_exists($tmp_img)) unlink($tmp_img);
    } else {
        throw new Exception("No se recibió ningún archivo válido.");
    }

    // Registrar en archivos_adjuntos
    $stmtA = $pdo->prepare("INSERT INTO archivos_adjuntos (reintegro_id, nombre_archivo, ruta_archivo, tipo_archivo) VALUES (?, ?, ?, ?)");
    $stmtA->execute([$r_id, $nombre_archivo, $ruta_destino, strtolower($tipo)]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'id' => $r_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>