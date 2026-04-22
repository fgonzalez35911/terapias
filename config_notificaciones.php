<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';
verificarAcceso('configuracion_sistema'); // Solo vos como Admin

$usuarios = $pdo->query("SELECT id, nombre_completo, rol FROM usuarios WHERE id > 0")->fetchAll();
$config = $pdo->query("SELECT * FROM config_notificaciones")->fetchAll(PDO::FETCH_UNIQUE);

include 'includes/header.php';
?>

<h2>Configuración de Alertas Automáticas</h2>
<p class="text-muted">Marcá quiénes deben recibir avisos (Notificación en vivo y Email) para cada evento.</p>

<div class="card">
    <form id="formConfigNotif">
        <table class="table">
            <thead>
                <tr>
                    <th>Evento del Sistema</th>
                    <th>Usuarios que reciben la alerta</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $eventos = [
                    'documento_subido' => 'Cuando un Terapeuta sube Factura/Planilla',
                    'falta_firma' => 'Recordatorio de firma pendiente (Día 5)',
                    'falta_envio' => 'Alerta: Documentación no enviada (Día 8)',
                    'pago_recibido' => 'Cuando se registra un pago'
                ];

                foreach ($eventos as $key => $titulo): 
                    $destinos = explode(',', $config[$key]['usuarios_destino'] ?? '');
                ?>
                <tr>
                    <td><strong><?php echo $titulo; ?></strong></td>
                    <td>
                        <?php foreach ($usuarios as $u): ?>
                            <label style="margin-right: 15px; cursor:pointer;">
                                <input type="checkbox" name="<?php echo $key; ?>[]" value="<?php echo $u['id']; ?>" 
                                <?php echo in_array($u['id'], $destinos) ? 'checked' : ''; ?>> 
                                <?php echo htmlspecialchars($u['nombre_completo']); ?> (<?php echo $u['rol']; ?>)
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-end mt-4">
            <button type="submit" class="btn btn-success">Guardar Configuración de Alertas</button>
        </div>
    </form>
</div>

<script>
document.getElementById('formConfigNotif').addEventListener('submit', function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    fd.append('action', 'guardar_config_alertas');

    fetch('ajax_notificaciones.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') Swal.fire('Guardado', 'La lógica de alertas se actualizó.', 'success');
    });
});
</script>

<?php include 'includes/footer.php'; ?>