<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('historial');
include 'includes/header.php';

// Filtros dinámicos
$where = [];
$params = [];

// Si no tiene el permiso de Visión Global, solo ve lo suyo
if (!tienePermiso('ver_todo')) {
    $where[] = "r.usuario_id = ?";
    $params[] = $_SESSION['usuario_id'];
}

// Corrección: Usar la columna correcta de la BD
if (!empty($_GET['mes'])) {
    $where[] = "r.mes_correspondiente = ?";
    $params[] = $_GET['mes'];
}
if (!empty($_GET['estado'])) {
    $where[] = "r.estado = ?";
    $params[] = $_GET['estado'];
}

// Corrección: t.nombre en lugar de t.nombre_completo y sumamos la tabla de archivos para el PDF
$sql = "SELECT r.*, t.nombre as terapeuta, a.ruta_archivo 
        FROM reintegros r 
        LEFT JOIN terapeutas t ON r.terapeuta_id = t.id
        LEFT JOIN archivos_adjuntos a ON r.id = a.reintegro_id";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY r.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reintegros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $reintegros = [];
    $error_db = "Error al cargar la base de datos: " . $e->getMessage();
}

$meses_nombres = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
?>

<style>
    .filtros-box { background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow-sm); margin-bottom: 20px; border: 1px solid var(--border); }
    .badge-estado { padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; display: inline-block; text-align: center; }
    
    .st-pendiente { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .st-firmado { background: #e0e7ff; color: #0369a1; border: 1px solid #bae6fd; }
    .st-enviado { background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
    .st-recibido { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
    .st-pagado { background: #ecfdf5; color: #10b981; border: 1px solid #6ee7b7; }
    
    .table-hover tbody tr:hover { background-color: #f1f5f9; cursor: pointer; transition: 0.2s; transform: scale(1.01); box-shadow: var(--shadow-sm); }
</style>

<div class="flex-between">
    <h2>Historial de Reintegros y Documentación</h2>
    <?php if(tienePermiso('subir_documentos')): ?>
        <a href="subir_documentos.php" class="btn btn-primary">+ Nueva Carga</a>
    <?php endif; ?>
</div>

<div class="filtros-box">
    <form method="GET" action="historial.php" class="grid-3" style="align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 600; font-size: 0.9rem;">Filtrar por Mes</label>
            <select name="mes" class="form-control">
                <option value="">Todos los meses</option>
                <?php foreach($meses_nombres as $num => $nombre): ?>
                    <option value="<?php echo $num; ?>" <?php echo ($_GET['mes']??'')==$num ? 'selected':''; ?>><?php echo $nombre; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-weight: 600; font-size: 0.9rem;">Filtrar por Estado</label>
            <select name="estado" class="form-control">
                <option value="">Todos los estados</option>
                <option value="Pendiente" <?php echo ($_GET['estado']??'')=='Pendiente'?'selected':''; ?>>Pendiente de Firma</option>
                <option value="Firmado" <?php echo ($_GET['estado']??'')=='Firmado'?'selected':''; ?>>Firmado (Listo)</option>
                <option value="Enviado" <?php echo ($_GET['estado']??'')=='Enviado'?'selected':''; ?>>Enviado a IOSFA</option>
                <option value="Recibido" <?php echo ($_GET['estado']??'')=='Recibido'?'selected':''; ?>>Recibido (Aprobado)</option>
                <option value="Pagado Total" <?php echo ($_GET['estado']??'')=='Pagado Total'?'selected':''; ?>>Pagado Total</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary btn-full">🔍 Aplicar Filtros</button>
        </div>
    </form>
</div>

<div class="card" style="overflow-x: auto; padding: 0;">
    <?php if(isset($error_db)): ?>
        <div class="text-center p-4 text-danger fw-bold">
            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
            <p><?php echo $error_db; ?></p>
        </div>
    <?php else: ?>
        <table class="table table-hover" style="margin: 0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Terapeuta</th>
                    <th>Período</th>
                    <th>Estado IOSFA</th>
                    <th>Fecha Carga</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($reintegros) > 0): ?>
                    <?php foreach($reintegros as $r): 
                        // Colores alineados a la base de datos real
                        $estado_actual = !empty($r['estado']) ? $r['estado'] : 'Pendiente';
                        $clase_estado = 'st-pendiente';
                        if($estado_actual == 'Firmado') $clase_estado = 'st-firmado';
                        if($estado_actual == 'Enviado') $clase_estado = 'st-enviado';
                        if(in_array($estado_actual, ['Recibido', 'Pagado Parcial'])) $clase_estado = 'st-recibido';
                        if($estado_actual == 'Pagado Total') $clase_estado = 'st-pagado';
                        ?>
                    <tr>
                        <td style="font-weight: bold; color: var(--text-light);">#<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($r['terapeuta'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo isset($meses_nombres[$r['mes_correspondiente']]) ? $meses_nombres[$r['mes_correspondiente']] : '-'; ?> / <?php echo htmlspecialchars($r['anio_correspondiente'] ?? '-'); ?></td>
                        <td><span class="badge-estado <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($estado_actual); ?></span></td>
                        <td class="small text-muted"><?php echo htmlspecialchars($r['fecha_envio'] ?? 'Reciente'); ?></td>
                        <td style="display: flex; gap: 5px;">
                            <?php if(!empty($r['ruta_archivo'])): ?>
                                <button class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="window.open('<?php echo $r['ruta_archivo']; ?>', '_blank')" title="Ver PDF"><i class="fas fa-file-pdf"></i></button>
                            <?php endif; ?>

                            <?php if(tienePermiso('aprobar_reintegros')): ?>
                                <button class="btn btn-success" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cambiarEstado(<?php echo $r['id']; ?>, 'Recibido')" title="Aprobar"><i class="fas fa-check"></i></button>
                                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="cambiarEstado(<?php echo $r['id']; ?>, 'Rechazado')" title="Rechazar"><i class="fas fa-times"></i></button>
                            <?php endif; ?>

                            <?php if(tienePermiso('editar_reintegros')): ?>
                                <button class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem; color:black;" onclick="editarReintegro(<?php echo $r['id']; ?>)" title="Editar"><i class="fas fa-edit"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i><br>
                            No se encontraron registros con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script>
function editarReintegro(id) {
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
    const accionTexto = nuevoEstado === 'Recibido' ? 'marcar como APROBADO' : 'marcar como RECHAZADO';
    
    Swal.fire({
        title: '¿Confirmar cambio?',
        text: `Estás por ${accionTexto} este registro.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let fd = new FormData();
            fd.append('action', 'cambiar_estado');
            fd.append('id', id);
            fd.append('estado', nuevoEstado);
            fetch('ajax_reintegros.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('¡Listo!', 'El estado ha sido actualizado.', 'success').then(() => location.reload());
                } else { 
                    Swal.fire('Error', data.message, 'error'); 
                }
            });
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>