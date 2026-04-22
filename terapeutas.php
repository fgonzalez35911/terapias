<?php 
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: /terapias/login.php");
    exit;
}

require_once 'db.php';
include 'includes/header.php'; 

// Obtener la lista de terapeutas
$stmt = $pdo->query("SELECT * FROM terapeutas ORDER BY nombre ASC");
$terapeutas = $stmt->fetchAll();
?>

<div class="flex-between">
    <h2>Directorio de Terapeutas</h2>
    <button class="btn btn-primary" onclick="abrirModalTerapeuta()">+ Agregar Terapeuta</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Especialidad</th>
                <th>CUIT</th>
                <th>Valor Sesión</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($terapeutas) > 0): ?>
                <?php foreach($terapeutas as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($t['especialidad']); ?></td>
                    <td><?php echo htmlspecialchars($t['cuit']); ?></td>
                    <td>$<?php echo number_format($t['valor_sesion'], 2, ',', '.'); ?></td>
                    <td><?php echo $t['activo'] ? '<span style="color:var(--success);font-weight:bold;">Activo</span>' : '<span style="color:var(--danger);font-weight:bold;">Inactivo</span>'; ?></td>
                    <td>
                        <button class="btn btn-primary" onclick="editarTerapeuta(<?php echo htmlspecialchars(json_encode($t)); ?>)">Editar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No hay terapeutas cargados. Empezá agregando uno.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Función para Agregar o Editar usando SweetAlert2
function abrirModalTerapeuta(terapeuta = null) {
    const isEdit = terapeuta !== null;
    const id = isEdit ? terapeuta.id : '';
    const nombre = isEdit ? terapeuta.nombre : '';
    const especialidad = isEdit ? terapeuta.especialidad : '';
    const cuit = isEdit ? terapeuta.cuit : '';
    const valor = isEdit ? terapeuta.valor_sesion : '';

    Swal.fire({
        title: isEdit ? 'Editar Terapeuta' : 'Nuevo Terapeuta',
        html: `
            <input type="hidden" id="t_id" value="${id}">
            <input type="text" id="t_nombre" class="swal2-input" placeholder="Nombre completo" value="${nombre}">
            <input type="text" id="t_especialidad" class="swal2-input" placeholder="Especialidad (ej. Psicología)" value="${especialidad}">
            <input type="text" id="t_cuit" class="swal2-input" placeholder="CUIT (sin guiones)" value="${cuit}">
            <input type="number" id="t_valor" class="swal2-input" placeholder="Valor de la sesión $" value="${valor}">
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const data = {
                id: document.getElementById('t_id').value,
                nombre: document.getElementById('t_nombre').value,
                especialidad: document.getElementById('t_especialidad').value,
                cuit: document.getElementById('t_cuit').value,
                valor_sesion: document.getElementById('t_valor').value
            };
            if (!data.nombre) {
                Swal.showValidationMessage('El nombre es obligatorio');
            }
            return data;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            guardarTerapeuta(result.value);
        }
    });
}

function editarTerapeuta(terapeuta) {
    abrirModalTerapeuta(terapeuta);
}

function guardarTerapeuta(datos) {
    let formData = new FormData();
    for (let key in datos) {
        formData.append(key, datos[key]);
    }

    fetch('ajax_terapeutas.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('¡Guardado!', 'Los datos se actualizaron correctamente.', 'success')
            .then(() => location.reload()); // Recarga para ver los cambios en la tabla
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Hubo un problema de conexión', 'error'));
}
</script>

<?php include 'includes/footer.php'; ?>
