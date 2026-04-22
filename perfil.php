<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$mensaje = '';
$alerta_tipo = '';

// Procesar POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ACCIÓN: Actualizar Info ---
    if (isset($_POST['action']) && $_POST['action'] == 'actualizar_info') {
        $nombre = trim($_POST['nombre_completo']);
        $dni = trim($_POST['dni']);
        $email = trim($_POST['email']);
        $tel = trim($_POST['telefono']);
        $gen = $_POST['genero'] ?? 'otro';
        $fecha_nac = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
        $firma_b64 = $_POST['firma_base64_hidden'] ?? '';

        try {
            $sql = "UPDATE usuarios SET nombre_completo=?, dni=?, email=?, telefono=?, genero=?, fecha_nacimiento=? WHERE id=?";
            $pdo->prepare($sql)->execute([$nombre, $dni, $email, $tel, $gen, $fecha_nac, $id_usuario]);

            // Solo actualiza la firma si se dibujó una nueva
            if (!empty($firma_b64)) {
                $pdo->prepare("UPDATE usuarios SET firma_digital=? WHERE id=?")->execute([$firma_b64, $id_usuario]);
            }
            $_SESSION['nombre'] = $nombre; 
            $mensaje = "Datos guardados correctamente."; $alerta_tipo = 'success';
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage(); $alerta_tipo = 'danger';
        }
    }

    // --- ACCIÓN: Subir Foto Manual ---
    if (isset($_POST['action']) && $_POST['action'] == 'actualizar_foto') {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_perfil'];
            $dir = 'uploads/perfiles/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp']) && $file['size'] < 5000000) {
                $new = 'perfil_' . $id_usuario . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . $new)) {
                    $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
                    $stmt->execute([$id_usuario]);
                    $old = $stmt->fetchColumn();
                    if ($old && $old != 'default.png' && file_exists($dir.$old)) @unlink($dir.$old);
                    
                    $pdo->prepare("UPDATE usuarios SET foto_perfil=? WHERE id=?")->execute([$new, $id_usuario]);
                    $mensaje = "Foto subida correctamente."; $alerta_tipo = 'success';
                }
            } else { $mensaje = "Archivo inválido o muy pesado."; $alerta_tipo = 'warning'; }
        }
    }
    
    // --- ACCIÓN: Password ---
    if (isset($_POST['action']) && $_POST['action'] == 'actualizar_pass') {
        $act = $_POST['password_actual']; $nue = $_POST['password_nueva']; $conf = $_POST['password_confirmar'];
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$id_usuario]);
        $hash_actual = $stmt->fetchColumn();
        
        if (password_verify($act, $hash_actual)) {
            if (strlen($nue)>=6 && $nue===$conf) {
                $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?")->execute([password_hash($nue, PASSWORD_DEFAULT), $id_usuario]);
                $mensaje = "Contraseña cambiada con éxito."; $alerta_tipo = 'success';
            } else { $mensaje = "Error en la confirmación de la nueva contraseña."; $alerta_tipo = 'danger'; }
        } else { $mensaje = "La contraseña actual es incorrecta."; $alerta_tipo = 'danger'; }
    }
}

// Cargar Datos
$stmt_load = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_load->execute([$id_usuario]);
$usuario_data = $stmt_load->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php'; 
?>

<style>
    .perfil-container { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; }
    .perfil-sidebar { width: 320px; flex-shrink: 0; }
    .perfil-main { flex-grow: 1; min-width: 300px; }
    
    .header-tarjeta { background: var(--primary); color: white; padding: 15px; border-radius: 12px 12px 0 0; font-weight: 600; font-size: 1.1rem; }
    .header-seguridad { background: var(--warning); color: var(--primary); padding: 15px; border-radius: 12px 12px 0 0; font-weight: 600; font-size: 1.1rem; }
    .cuerpo-tarjeta { padding: 25px; border: 1px solid var(--border); border-top: none; border-radius: 0 0 12px 12px; background: white; margin-bottom: 25px; box-shadow: var(--shadow-sm); }
    
    .foto-preview { width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg); box-shadow: var(--shadow-md); margin: 0 auto 15px auto; display: block; }
    
    .firma-box { border: 2px dashed var(--secondary); border-radius: 8px; height: 140px; display: flex; align-items: center; justify-content: center; background: #f8fafc; cursor: pointer; text-align: center; transition: all 0.3s; }
    .firma-box:hover { background: #e2e8f0; border-color: var(--primary); transform: translateY(-2px); }
    
    .badge-rol { background: var(--secondary); color: white; padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; text-transform: uppercase; display: inline-block; font-weight: bold; margin-bottom: 15px; }
    
    #canvasContainerPerfil { width: 100%; height: 70vh; background: white; border: 2px solid #ccc; position: relative; border-radius: 8px; overflow: hidden; }
    
    .alerta-perfil { padding: 15px; border-radius: 8px; font-weight: 500; margin-bottom: 20px; border-left: 5px solid; }
    .alerta-success { background: #d1fae5; color: #065f46; border-left-color: var(--success); }
    .alerta-danger { background: #fee2e2; color: #991b1b; border-left-color: var(--danger); }
    
    @media (max-width: 768px) {
        .perfil-sidebar { width: 100%; }
    }
</style>

<div class="flex-between" style="margin-top: 1rem;">
    <h2>Gestión de Perfil</h2>
</div>

<?php if ($mensaje): ?>
    <div class="alerta-perfil alerta-<?php echo $alerta_tipo; ?>">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="perfil-container">
    <div class="perfil-sidebar">
        <div class="header-tarjeta">Tu Foto de Perfil</div>
        <div class="cuerpo-tarjeta text-center">
            <img src="uploads/perfiles/<?php echo htmlspecialchars($usuario_data['foto_perfil'] ?? 'default.png'); ?>" class="foto-preview">
            <div class="badge-rol"><?php echo htmlspecialchars($usuario_data['rol']); ?></div>
            
            <form action="perfil.php" method="POST" enctype="multipart/form-data" style="margin-top: 15px; text-align: left;">
                <input type="hidden" name="action" value="actualizar_foto">
                <div class="form-group">
                    <label class="small" style="font-weight: 600;">Subir nueva foto:</label>
                    <input class="form-control" type="file" name="foto_perfil" accept="image/*" style="padding: 0.5rem;" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Actualizar Foto</button>
            </form>
        </div>
    </div>

    <div class="perfil-main">
        <div class="header-tarjeta">Datos Personales</div>
        <div class="cuerpo-tarjeta">
            <form action="perfil.php" method="POST">
                <input type="hidden" name="action" value="actualizar_info">
                
                <div class="grid-3" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group"><label>Nombre Completo</label><input type="text" class="form-control" name="nombre_completo" value="<?php echo htmlspecialchars($usuario_data['nombre_completo']); ?>" required></div>
                    <div class="form-group"><label>DNI</label><input type="text" class="form-control" name="dni" value="<?php echo htmlspecialchars($usuario_data['dni']); ?>" required></div>
                </div>
                
                <div class="grid-3" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group"><label>Usuario del Sistema</label><input type="text" class="form-control" style="background:#e2e8f0; color: #64748b; font-weight: bold;" value="<?php echo htmlspecialchars($usuario_data['usuario']); ?>" disabled></div>
                    <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($usuario_data['email'] ?? ''); ?>"></div>
                </div>

                <div class="grid-3">
                    <div class="form-group"><label>Teléfono</label><input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario_data['telefono'] ?? ''); ?>"></div>
                    <div class="form-group">
                        <label>Género</label>
                        <select class="form-control" name="genero">
                            <option value="masculino" <?php echo ($usuario_data['genero']=='masculino')?'selected':''; ?>>Masculino</option>
                            <option value="femenino" <?php echo ($usuario_data['genero']=='femenino')?'selected':''; ?>>Femenino</option>
                            <option value="otro" <?php echo ($usuario_data['genero']=='otro')?'selected':''; ?>>Otro</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Fecha de Nacimiento</label><input type="date" class="form-control" name="fecha_nacimiento" value="<?php echo htmlspecialchars($usuario_data['fecha_nacimiento'] ?? ''); ?>"></div>
                </div>

                <hr style="margin: 25px 0; border-color: var(--border);">
                
                <h4 style="margin-bottom: 15px; color: var(--primary);">Firma Digital Autenticada</h4>
                <div class="grid-3" style="grid-template-columns: 1fr 1fr; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="small text-muted" style="font-weight: 600;">Firma Actual Guardada</label>
                        <div style="border: 1px solid var(--border); border-radius: 8px; height: 140px; display: flex; align-items: center; justify-content: center; background: white;">
                            <?php if (!empty($usuario_data['firma_digital'])): ?>
                                <img src="<?php echo $usuario_data['firma_digital']; ?>" style="max-width:90%; max-height:90%;">
                            <?php else: ?>
                                <span style="color:#999; font-style:italic;">Sin firma guardada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="small text-muted" style="font-weight: 600;">Configurar Nueva Firma</label>
                        <div class="firma-box" onclick="abrirModalFirmaPerfil()">
                            <div id="preview_firma_nueva">
                                <span style="font-size: 32px; color: var(--secondary); display: block; margin-bottom: 5px;">🖋️</span>
                                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text);">Tocar aquí para firmar</span>
                            </div>
                        </div>
                        <input type="hidden" name="firma_base64_hidden" id="firma_base64_hidden">
                    </div>
                </div>

                <div style="text-align: right; margin-top: 25px;">
                    <button type="submit" class="btn btn-success" style="padding: 0.8rem 2rem; font-size: 1rem;">Guardar Datos Personales</button>
                </div>
            </form>
        </div>

        <div class="header-seguridad">Seguridad de la Cuenta</div>
        <div class="cuerpo-tarjeta">
            <form action="perfil.php" method="POST">
                <input type="hidden" name="action" value="actualizar_pass">
                <div class="grid-3">
                    <div class="form-group"><label>Contraseña Actual</label><input type="password" class="form-control" name="password_actual" required></div>
                    <div class="form-group"><label>Nueva Contraseña</label><input type="password" class="form-control" name="password_nueva" minlength="6" required></div>
                    <div class="form-group"><label>Repetir Contraseña</label><input type="password" class="form-control" name="password_confirmar" minlength="6" required></div>
                </div>
                <div style="text-align: right; margin-top: 10px;">
                    <button type="submit" class="btn" style="background: var(--warning); color: #000;">Actualizar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalFirma" class="hidden" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.9); z-index:10001; display:flex; align-items:center; justify-content:center;">
    <div style="background:var(--bg); padding:20px; border-radius:12px; width:95%; max-width:900px; box-shadow: var(--shadow-lg);">
        <div class="flex-between" style="border-bottom: 2px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: var(--primary);">Panel de Firma Digital</h3>
            <button class="btn btn-danger" onclick="cerrarFirma()" style="padding: 5px 15px;">✕ Cerrar</button>
        </div>
        
        <div id="canvasContainerPerfil">
            <canvas id="pad" style="width:100%; height:100%; display:block; touch-action:none;"></canvas>
            <div style="position: absolute; top: 70%; left: 5%; right: 5%; border-bottom: 2px dashed #94a3b8; pointer-events: none;"></div>
            <div style="position: absolute; top: 72%; width: 100%; text-align: center; color: #94a3b8; font-weight: bold; pointer-events: none; letter-spacing: 2px;">ÁREA DE FIRMA</div>
        </div>
        
        <div class="flex-between" style="margin-top: 20px;">
            <button class="btn btn-danger" onclick="pad.clear()" style="padding: 0.8rem 2rem;">Borrar Todo</button>
            <button class="btn btn-success" onclick="aceptarFirma()" style="padding: 0.8rem 2rem; font-weight: bold;">ACEPTAR FIRMA</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    let pad;
    
    function abrirModalFirmaPerfil() {
        document.getElementById('modalFirma').classList.remove('hidden');
        const canvas = document.getElementById('pad');
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        
        if(pad) pad.clear();
        pad = new SignaturePad(canvas, { 
            penColor: "black",
            minWidth: 1.5,
            maxWidth: 3.5
        });
    }
    
    function cerrarFirma() { 
        document.getElementById('modalFirma').classList.add('hidden'); 
    }
    
    function aceptarFirma() {
        if(pad.isEmpty()) {
            Swal.fire('Atención', 'Debe dibujar su firma antes de aceptar.', 'warning');
            return;
        }
        
        const base64 = pad.toDataURL('image/png');
        document.getElementById('firma_base64_hidden').value = base64;
        
        const preview = document.getElementById('preview_firma_nueva');
        preview.innerHTML = `<img src="${base64}" style="max-height:120px; max-width:100%;">`;
        preview.parentElement.style.borderColor = 'var(--success)';
        preview.parentElement.style.borderStyle = 'solid';
        
        cerrarFirma();
    }
</script>

<?php include 'includes/footer.php'; ?>