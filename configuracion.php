<?php 
session_start();
require_once 'db.php';
require_once 'includes/roles.php';
verificarAcceso('configuracion_sistema');

include 'includes/header.php'; 

// Cargar configs
$conf = $pdo->query("SELECT * FROM configuracion WHERE id = 1")->fetch();
$usuarios = $pdo->query("SELECT id, nombre_completo, rol FROM usuarios ORDER BY nombre_completo ASC")->fetchAll();
$alertas_db = $pdo->query("SELECT * FROM config_notificaciones")->fetchAll(PDO::FETCH_UNIQUE);
?>

<div class="flex-between">
    <h2><i class="fas fa-cogs me-2"></i> Ajustes del Sistema</h2>
</div>

<div class="grid-3">
    <div class="card">
        <h3><i class="fas fa-server me-2"></i> Servidor de Correo</h3>
        <form id="formSMTP">
            <div class="form-group"><label>Host SMTP</label><input type="text" id="s_host" class="form-control" value="<?php echo $conf['smtp_host'] ?? ''; ?>"></div>
            <div class="form-group"><label>Usuario/Email</label><input type="text" id="s_user" class="form-control" value="<?php echo $conf['smtp_user'] ?? ''; ?>"></div>
            <div class="form-group"><label>Password</label><input type="password" id="s_pass" class="form-control" value="<?php echo $conf['smtp_pass'] ?? ''; ?>"></div>
            <div class="form-group"><label>Destino IOSFA</label><input type="email" id="s_destino" class="form-control" value="<?php echo $conf['email_reintegros'] ?? ''; ?>"></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar SMTP</button>
        </form>
    </div>

    <div class="card">
        <h3><i class="fas fa-envelope-open-text me-2"></i> Plantilla de Mail</h3>
        <form id="formTemplate">
            <div class="form-group"><label>Asunto</label><input type="text" id="s_asunto" class="form-control" value="<?php echo $conf['asunto_predeterminado'] ?? ''; ?>"></div>
            <div class="form-group"><label>Cuerpo del Mensaje</label><textarea id="s_cuerpo" class="form-control" rows="8"><?php echo $conf['cuerpo_email_base'] ?? ''; ?></textarea></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar Plantilla</button>
        </form>
    </div>

    <div class="card" style="border: 2px solid var(--danger);">
        <h3 style="color: var(--danger);"><i class="fas fa-exclamation-triangle me-2"></i> Zona Crítica</h3>
        <p class="small text-muted">Acciones de limpieza profunda.</p>
        <div class="d-grid gap-2 mt-4">
            <button class="btn btn-danger btn-full" onclick="confirmarLimpieza('reintegros')">Borrar Datos de Prueba</button>
            <button class="btn btn-danger btn-full" style="opacity: 0.6; margin-top:10px;" onclick="confirmarLimpieza('total')">Resetear Sistema</button>
        </div>
    </div>
</div>

<div class="card mt-4">
    <h3><i class="fas fa-bell me-2"></i> Configuración de Alertas Automáticas</h3>
    <p class="small text-muted mb-4">Elegí quién recibe notificaciones por cada evento.</p>
    <form id="formAlertas">
        <table class="table">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Destinatarios</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $eventos = [
                    'documento_subido' => 'Un terapeuta sube documentación',
                    'falta_firma' => 'Recordatorio de firma (Día 5)',
                    'falta_envio' => 'Alerta de envío pendiente (Día 8)',
                    'pago_recibido' => 'Se registra un pago'
                ];
                foreach ($eventos as $key => $titulo): 
                    $destinos = explode(',', $alertas_db[$key]['usuarios_destino'] ?? '');
                ?>
                <tr>
                    <td><strong><?php echo $titulo; ?></strong></td>
                    <td>
                        <?php foreach ($usuarios as $u): ?>
                            <label style="margin-right: 20px; cursor:pointer;">
                                <input type="checkbox" name="<?php echo $key; ?>[]" value="<?php echo $u['id']; ?>" 
                                <?php echo in_array($u['id'], $destinos) ? 'checked' : ''; ?>> 
                                <?php echo htmlspecialchars($u['nombre_completo']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success">Guardar Preferencias de Alerta</button>
        </div>
    </form>
</div>

<script>
// Manejo de SMTP
document.getElementById('formSMTP').addEventListener('submit', function(e) {
    e.preventDefault();
    enviarConfig({ 
        accion: 'smtp', 
        host: document.getElementById('s_host').value, 
        user: document.getElementById('s_user').value, 
        pass: document.getElementById('s_pass').value, 
        destino: document.getElementById('s_destino').value 
    });
});

// Manejo de Plantilla
document.getElementById('formTemplate').addEventListener('submit', function(e) {
    e.preventDefault();
    enviarConfig({ 
        accion: 'template', 
        asunto: document.getElementById('s_asunto').value, 
        cuerpo: document.getElementById('s_cuerpo').value 
    });
});

// Manejo de Alertas (Integrado)
document.getElementById('formAlertas').addEventListener('submit', function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    fd.append('accion', 'guardar_config_alertas');
    fetch('ajax_configuracion.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') Swal.fire('¡Listo!', 'Preferencias de alerta actualizadas.', 'success');
    });
});

function enviarConfig(objeto) {
    let fd = new FormData();
    for (let k in objeto) fd.append(k, objeto[k]);
    fetch('ajax_configuracion.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') Swal.fire('Éxito', 'Cambios guardados.', 'success');
        else Swal.fire('Error', data.message, 'error');
    });
}

function confirmarLimpieza(tipo) {
    Swal.fire({
        title: '¿Confirmar borrado?',
        text: "Esta acción es irreversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, borrar'
    }).then((result) => {
        if (result.isConfirmed) {
            let fd = new FormData();
            fd.append('accion', 'limpiar_datos');
            fd.append('tipo', tipo);
            fetch('ajax_configuracion.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => location.reload());
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>