<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$reintegro_id = $_POST['id'] ?? '';

try {
    // 1. Obtener datos del reintegro y archivo
    $stmt = $pdo->prepare("
        SELECT r.*, t.nombre as terapeuta, a.ruta_archivo 
        FROM reintegros r 
        JOIN terapeutas t ON r.terapeuta_id = t.id 
        JOIN archivos_adjuntos a ON r.id = a.reintegro_id 
        WHERE r.id = ?
    ");
    $stmt->execute([$reintegro_id]);
    $data = $stmt->fetch();

    if (!$data || empty($data['ruta_archivo'])) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró el archivo adjunto para este expediente.']);
        exit;
    }

    // 2. Obtener configuración SMTP desde la base de datos
    $conf = $pdo->query("SELECT * FROM configuracion WHERE id = 1")->fetch();

    if (empty($conf['smtp_user']) || empty($conf['smtp_pass'])) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan los datos del servidor de correo en Ajustes.']);
        exit;
    }

    // --- LÓGICA NATIVA DE HOSTINGER (Extraída de tu sistema) ---
    $usuario_mail = $conf['smtp_user']; 
    $password_mail = $conf['smtp_pass']; 
    $servidor = 'ssl://' . $conf['smtp_host'];
    $puerto = $conf['smtp_port'];
    $destinatario = $conf['email_reintegros']; // El mail de IOSFA

    $hora = date('H');
    $saludo = ($hora < 12) ? "Buenos días" : (($hora < 20) ? "Buenas tardes" : "Buenas noches");
    $meses = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
    $mes_nombre = $meses[$data['mes_correspondiente']];

    $asunto = "Reintegros - Factura y Planilla - " . $mes_nombre . " - " . $data['terapeuta'];
    $cuerpoHTML = "
        <p>$saludo,</p>
        <p>Adjunto al presente correo la factura y planilla correspondientes a la terapia de mi hijo del mes de <b>$mes_nombre</b>, prestada por <b>" . $data['terapeuta'] . "</b>.</p>
        <p>Quedo a la espera de la confirmación de recepción.</p>
        <p>Saludos cordiales,<br><b>" . $_SESSION['nombre'] . "</b><br>DNI: " . $conf['dni'] . "</p>
    ";
    
    $adjuntoPath = $data['ruta_archivo'];

    // --- CONEXIÓN POR SOCKETS ---
    $contexto = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("$servidor:$puerto", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $contexto);

    if (!$socket) {
        echo json_encode(['status' => 'error', 'message' => "Error Conexión Hostinger: $errstr ($errno)"]);
        exit;
    }

    function leer_socket($s) {
        $d = "";
        while($str = @fgets($s, 515)) {
            $d .= $str;
            if(substr($str, 3, 1) == " ") break;
        }
        return $d;
    }

    function escribir_socket($s, $c) {
        @fputs($s, $c . "\r\n");
        return leer_socket($s);
    }

    leer_socket($socket);
    escribir_socket($socket, "EHLO " . $_SERVER['HTTP_HOST']);
    escribir_socket($socket, "AUTH LOGIN");
    escribir_socket($socket, base64_encode($usuario_mail));
    escribir_socket($socket, base64_encode($password_mail));

    escribir_socket($socket, "MAIL FROM: <$usuario_mail>");
    escribir_socket($socket, "RCPT TO: <$destinatario>");
    escribir_socket($socket, "DATA");

    $boundary = md5(uniqid(time()));
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: Sistema SGR <$usuario_mail>\r\n";
    $headers .= "To: <$destinatario>\r\n";
    $headers .= "Subject: $asunto\r\n";
    $headers .= "Date: " . date("r") . "\r\n";

    if ($adjuntoPath && file_exists($adjuntoPath)) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $cuerpo_final = "--$boundary\r\n";
        $cuerpo_final .= "Content-Type: text/html; charset=UTF-8\r\n";
        $cuerpo_final .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $cuerpo_final .= $cuerpoHTML . "\r\n\r\n";
        
        $archivo_contenido = file_get_contents($adjuntoPath);
        $archivo_codificado = chunk_split(base64_encode($archivo_contenido));
        $nombre_archivo = basename($adjuntoPath);
        
        $cuerpo_final .= "--$boundary\r\n";
        $cuerpo_final .= "Content-Type: application/pdf; name=\"$nombre_archivo\"\r\n";
        $cuerpo_final .= "Content-Transfer-Encoding: base64\r\n";
        $cuerpo_final .= "Content-Disposition: attachment; filename=\"$nombre_archivo\"\r\n\r\n";
        $cuerpo_final .= $archivo_codificado . "\r\n\r\n";
        $cuerpo_final .= "--$boundary--\r\n";
    } else {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $cuerpo_final = $cuerpoHTML;
    }

    @fputs($socket, "$headers\r\n$cuerpo_final\r\n.\r\n");
    $resultado = leer_socket($socket);
    
    @fputs($socket, "QUIT\r\n");
    @fclose($socket);

    // Si Hostinger devuelve el código 250, el mail salió perfecto
    if (strpos($resultado, '250') !== false) {
        $pdo->prepare("UPDATE reintegros SET estado = 'Enviado', fecha_envio = NOW() WHERE id = ?")->execute([$reintegro_id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Error SMTP Hostinger: " . $resultado]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => "Error general: {$e->getMessage()}"]);
}
?>
