<?php 
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: /terapias/login.php");
    exit;
}

require_once 'db.php';
require_once 'includes/roles.php';
include 'includes/header.php'; 

$usuario_id = $_SESSION['usuario_id'];
$vision_global = tienePermiso('ver_todo');

$filtro_and = $vision_global ? "" : " AND usuario_id = " . (int)$usuario_id;
$filtro_where = $vision_global ? "" : " WHERE r.usuario_id = " . (int)$usuario_id;

// 1. Obtener estadísticas para las tarjetas
$stmt1 = $pdo->query("SELECT COUNT(*) FROM reintegros WHERE estado = 'Pendiente'" . $filtro_and);
$pendientes_firma = $stmt1->fetchColumn();

$stmt2 = $pdo->query("SELECT COUNT(*) FROM reintegros WHERE estado = 'Enviado'" . $filtro_and);
$enviados = $stmt2->fetchColumn();

$mes_actual = date('n');
$anio_actual = date('Y');
$stmt3 = $pdo->query("SELECT SUM(monto_total_pagado) FROM reintegros WHERE mes_correspondiente = $mes_actual AND anio_correspondiente = $anio_actual" . $filtro_and);
$cobrado_mes = $stmt3->fetchColumn() ?: 0;

// 2. Obtener el historial de reintegros
$stmtH = $pdo->query("
    SELECT r.*, t.nombre as terapeuta_nombre, a.ruta_archivo 
    FROM reintegros r 
    JOIN terapeutas t ON r.terapeuta_id = t.id 
    LEFT JOIN archivos_adjuntos a ON r.id = a.reintegro_id
    " . $filtro_where . " 
    ORDER BY r.id DESC
");
$historial = $stmtH->fetchAll();

$meses_nombres = [1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic'];
?>

<div class="flex-between">
    <h2>Panel de Control</h2>
    <a href="/terapias/subir_documentos.php" class="btn btn-primary">+ Iniciar Nuevo Reintegro</a>
</div>

<div class="grid-3">
    <div class="card stat-card <?php echo ($pendientes_firma > 0) ? 'danger-border' : ''; ?>">
        <h3>Pendientes de Firma</h3>
        <p><?php echo $pendientes_firma; ?></p>
    </div>
    <div class="card stat-card">
        <h3>Enviados a Reintegros</h3>
        <p><?php echo $enviados; ?></p>
    </div>
    <div class="card stat-card success-border">
        <h3>Cobrado en <?php echo $meses_nombres[$mes_actual]; ?></h3>
        <p>$<?php echo number_format($cobrado_mes, 2, ',', '.'); ?></p>
    </div>
</div>

<div class="card">
    <div class="flex-between">
        <h3>Últimos Movimientos</h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Mes/Año</th>
                    <th>Terapeuta</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($historial) > 0): ?>
                    <?php foreach($historial as $h): ?>
                    <tr>
                        <td><?php echo $meses_nombres[$h['mes_correspondiente']] . ' ' . $h['anio_correspondiente']; ?></td>
                        <td><?php echo htmlspecialchars($h['terapeuta_nombre']); ?></td>
                        <td>$<?php echo number_format($h['monto_total_facturado'], 2, ',', '.'); ?></td>
                        <td>
                            <?php 
                                $color = 'var(--text)';
                                if($h['estado'] == 'Firmado') $color = 'var(--secondary)';
                                if($h['estado'] == 'Enviado') $color = '#f39c12';
                                if($h['estado'] == 'Pagado Total') $color = 'var(--success)';
                            ?>
                            <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                <?php echo $h['estado']; ?>
                            </span>
                        </td>
                        <td style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if($h['ruta_archivo']): ?>
                                <a href="/terapias/<?php echo $h['ruta_archivo']; ?>" target="_blank" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;" title="Ver Documento"><i class="fas fa-file-pdf"></i></a>
                            <?php endif; ?>
                            
                            <button class="btn btn-success" style="padding: 5px 10px; font-size: 0.8rem;" onclick="enviarPorCorreo(<?php echo $h['id']; ?>)" title="Enviar Correo"><i class="fas fa-paper-plane"></i></button>

                            <?php if(tienePermiso('editar_reintegros')): ?>
                                <button class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem; color:black;" onclick="editarReintegro(<?php echo $h['id']; ?>)" title="Editar"><i class="fas fa-edit"></i></button>
                            <?php endif; ?>

                            <?php if(tienePermiso('eliminar_reintegros')): ?>
                                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="eliminarReintegro(<?php echo $h['id']; ?>)" title="Eliminar"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Todavía no hay planillas cargadas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function editarReintegro(id) {
    window.location.href = 'editar_reintegro.php?id=' + id;
}

function enviarPorCorreo(id) {
    Swal.fire({
        title: '¿Enviar a la Obra Social?',
        text: "Se enviará el correo automático con el PDF adjunto.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            let formData = new FormData();
            formData.append('id', id);

            fetch('ajax_enviar_correo.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('¡Enviado!', 'El correo ha sido enviado correctamente.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

function editarReintegro(id) {
    // Te redirige a la página de edición. Si tu archivo se llama distinto, cambialo acá.
    window.location.href = 'editar_reintegro.php?id=' + id;
}

function eliminarReintegro(id) {
    Swal.fire({
        title: '¿Eliminar este registro?',
        text: "Esta acción borrará el reintegro y su archivo adjunto para siempre.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            let fd = new FormData();
            fd.append('action', 'eliminar');
            fd.append('id', id);
            fetch('ajax_reintegros.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Eliminado', '', 'success').then(() => location.reload());
                } else { Swal.fire('Error', data.message, 'error'); }
            });
        }
    });
}

function cambiarEstado(id, nuevoEstado) {
    let fd = new FormData();
    fd.append('action', 'cambiar_estado');
    fd.append('id', id);
    fd.append('estado', nuevoEstado);
    fetch('ajax_reintegros.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('Estado Actualizado', '', 'success').then(() => location.reload());
        } else { Swal.fire('Error', data.message, 'error'); }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
