<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('editar_reintegros');
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: historial.php"); exit; }

// Obtener datos del reintegro
$stmt = $pdo->prepare("SELECT r.*, t.nombre as terapeuta FROM reintegros r LEFT JOIN terapeutas t ON r.terapeuta_id = t.id WHERE r.id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) { echo "<h2>Registro no encontrado.</h2>"; include 'includes/footer.php'; exit; }

$meses = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
?>

<div class="card" style="max-width: 700px; margin: 20px auto;">
    <h2 style="border-bottom: 2px solid var(--border); padding-bottom: 10px; margin-bottom: 20px;">Editar Registro #<?php echo str_pad($id, 4, '0', STR_PAD_LEFT); ?></h2>
    
    <form id="formEditar">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="grid-3" style="grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label style="font-weight: 700;">Mes del Período</label>
                <select name="mes" class="form-control">
                    <?php foreach($meses as $num => $nombre): ?>
                        <option value="<?php echo $num; ?>" <?php echo $r['mes_correspondiente'] == $num ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight: 700;">Año</label>
                <input type="number" name="anio" class="form-control" value="<?php echo $r['anio_correspondiente']; ?>">
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label style="font-weight: 700;">Monto Facturado ($)</label>
            <input type="number" step="0.01" name="monto_facturado" class="form-control" value="<?php echo $r['monto_total_facturado']; ?>">
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label style="font-weight: 700;">Estado en IOSFA</label>
            <select name="estado" class="form-control">
                <option value="Pendiente" <?php echo $r['estado'] == 'Pendiente' ? 'selected' : ''; ?>>Pendiente de Firma</option>
                <option value="Firmado" <?php echo $r['estado'] == 'Firmado' ? 'selected' : ''; ?>>Firmado</option>
                <option value="Enviado" <?php echo $r['estado'] == 'Enviado' ? 'selected' : ''; ?>>Enviado</option>
                <option value="Recibido" <?php echo $r['estado'] == 'Recibido' ? 'selected' : ''; ?>>Recibido</option>
                <option value="Pagado Total" <?php echo $r['estado'] == 'Pagado Total' ? 'selected' : ''; ?>>Pagado Total</option>
            </select>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Guardar Cambios</button>
            <a href="historial.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.getElementById('formEditar').addEventListener('submit', function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    
    fetch('ajax_reintegros.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('¡Éxito!', 'Los datos han sido actualizados.', 'success').then(() => window.location.href = 'historial.php');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>