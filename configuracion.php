<?php 
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: /terapias/login.php");
    exit;
}

require_once 'db.php';
include 'includes/header.php'; 

// Traemos la configuración y los datos del usuario
$stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtU->execute([$_SESSION['usuario_id']]);
$user = $stmtU->fetch();

$stmtC = $pdo->query("SELECT * FROM configuracion WHERE id = 1");
$conf = $stmtC->fetch();
?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<h2>Configuración del Sistema</h2>

<div class="grid-3">
    <div class="card">
        <h3>Mis Datos y Firma</h3>
        <form id="formPerfil">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" id="p_nombre" class="form-control" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>">
            </div>
            <div class="form-group">
                <label>DNI (Sin puntos)</label>
                <input type="text" id="p_dni" class="form-control" value="<?php echo htmlspecialchars($user['dni']); ?>">
            </div>
            
            <div class="form-group">
                <label>Firma Digital Guardada</label>
                <div style="display:flex; align-items:center; gap:10px; margin-top:10px; border:1px solid var(--border); padding:10px; border-radius:5px; background:#f9f9f9; cursor:pointer;" onclick="abrirModalFirmaPerfil()">
                    <div id="previewFirma" style="flex-grow:1; text-align:center;">
                        <?php if(!empty($user['firma_digital'])): ?>
                            <img src="<?php echo $user['firma_digital']; ?>" style="max-height:60px;">
                        <?php else: ?>
                            <span style="color:#999;">Sin firma configurada</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:1.5rem; color:var(--secondary);">🖋️</div>
                </div>
                <small style="color:#666;">Hacé clic en el icono para dibujar o cambiar tu firma.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:15px;">Actualizar Mis Datos</button>
        </form>
    </div>

    <div class="card" style="grid-column: span 2;">
        <h3>Servidor de Correo (Hostinger SMTP)</h3>
        <form id="formSMTP">
            <div class="grid-3" style="gap: 10px;">
                <div class="form-group">
                    <label>Servidor SMTP</label>
                    <input type="text" id="s_host" class="form-control" value="<?php echo htmlspecialchars($conf['smtp_host'] ?? 'smtp.hostinger.com'); ?>">
                </div>
                <div class="form-group">
                    <label>Puerto</label>
                    <input type="number" id="s_port" class="form-control" value="<?php echo htmlspecialchars($conf['smtp_port'] ?? '465'); ?>">
                </div>
                <div class="form-group">
                    <label>Usuario Email</label>
                    <input type="text" id="s_user" class="form-control" value="<?php echo htmlspecialchars($conf['smtp_user'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Contraseña Email</label>
                <input type="password" id="s_pass" class="form-control" value="<?php echo htmlspecialchars($conf['smtp_pass'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email de Reintegros (IOSFA)</label>
                <input type="email" id="s_destino" class="form-control" value="<?php echo htmlspecialchars($conf['email_reintegros'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-success btn-full">Guardar Ajustes SMTP</button>
        </form>
    </div>
</div>

<script>
// Lógica para el Modal de Firma con SweetAlert2
function abrirModalFirmaPerfil() {
    Swal.fire({
        title: 'Dibujá tu firma',
        html: `
            <div style="border:2px dashed #ccc; background:#fff; touch-action:none; margin-bottom:10px;">
                <canvas id="pad" style="width:100%; height:200px;"></canvas>
            </div>
            <button type="button" class="btn btn-danger" onclick="window.pad.clear()">Limpiar</button>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirmar y Guardar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const canvas = document.getElementById('pad');
            // Ajuste de resolución para que no se vea pixelada
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            
            window.pad = new SignaturePad(canvas, { penColor: "black" });
        },
        preConfirm: () => {
            if (window.pad.isEmpty()) {
                Swal.showValidationMessage('Primero debés dibujar tu firma');
                return false;
            }
            return window.pad.toDataURL();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            guardarFirmaBase64(result.value);
        }
    });
}

function guardarFirmaBase64(base64) {
    let fd = new FormData();
    fd.append('accion', 'firma');
    fd.append('firma_b64', base64);

    fetch('ajax_configuracion.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('¡Éxito!', 'Firma guardada correctamente.', 'success');
            document.getElementById('previewFirma').innerHTML = `<img src="${base64}" style="max-height:60px;">`;
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

// Guardar Perfil y SMTP (Sin borrar lo que tenías)
document.getElementById('formPerfil').addEventListener('submit', function(e) {
    e.preventDefault();
    enviarConfig({ accion: 'perfil', nombre: document.getElementById('p_nombre').value, dni: document.getElementById('p_dni').value });
});

document.getElementById('formSMTP').addEventListener('submit', function(e) {
    e.preventDefault();
    enviarConfig({ 
        accion: 'smtp', 
        host: document.getElementById('s_host').value, 
        port: document.getElementById('s_port').value, 
        user: document.getElementById('s_user').value, 
        pass: document.getElementById('s_pass').value, 
        destino: document.getElementById('s_destino').value 
    });
});

function enviarConfig(objeto) {
    let fd = new FormData();
    for (let k in objeto) fd.append(k, objeto[k]);
    fetch('ajax_configuracion.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') Swal.fire('¡Listo!', 'Cambios guardados.', 'success');
        else Swal.fire('Error', data.message, 'error');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
