<?php 
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('configuracion_sistema');

include 'includes/header.php'; 

$stmt = $pdo->query("SELECT * FROM roles_dinamicos ORDER BY nombre_rol ASC");
$roles_db = $stmt->fetchAll();

// LOS PERMISOS NO SON COLUMNAS, SE GUARDAN ADENTRO DE LA COLUMNA 'permisos_json' EN LA TABLA 'roles_dinamicos'
$lista_permisos_sistema = [
    'subir_documentos' => 'Subir Facturas y Planillas',
    'gestionar_terapeutas' => 'Crear/Editar Terapeutas',
    'configuracion_sistema' => 'Acceso a Ajustes y Roles',
    'pacientes' => 'Ver listado de Pacientes',
    'historial' => 'Ver Historial de Reintegros',
    'reportes' => 'Ver Reportes y Gráficos',
    'notificaciones' => 'Acceder a Notificaciones',
    'ayuda' => 'Ver Centro de Ayuda',
    'ver_todo' => 'Visión Global (Ver registros de TODOS)',
    'aprobar_reintegros' => 'Aprobar o Rechazar Reintegros',
    'editar_reintegros' => 'Editar datos de Reintegros',
    'eliminar_reintegros' => 'Eliminar Reintegros',
    'exportar_datos' => 'Exportar datos a Excel/PDF'
];
?>

<div class="flex-between">
    <h2>Gestión Avanzada de Roles</h2>
    <button class="btn btn-primary" onclick="abrirModalRol()">+ Crear Nuevo Rol</button>
</div>

<div class="grid-3">
    <?php foreach($roles_db as $rol): 
        $permisos_activos = json_decode($rol['permisos_json'], true) ?? [];
    ?>
    <div class="card">
        <div class="flex-between" style="margin-bottom: 15px; border-bottom: 2px solid var(--border); padding-bottom: 10px;">
            <h3 style="margin: 0; color: var(--primary); text-transform: uppercase;"><?php echo htmlspecialchars($rol['nombre_rol']); ?></h3>
            <div>
                <button class="btn btn-warning" style="padding: 4px 8px; font-size: 0.8rem; color: black;" onclick='editarRol(<?php echo json_encode($rol); ?>)'>✏️ Editar</button>
                <button class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem;" onclick="eliminarRol(<?php echo $rol['id']; ?>, '<?php echo $rol['nombre_rol']; ?>')">✕</button>
            </div>
        </div>
        
        <div style="padding-bottom: 10px;">
            <?php foreach($lista_permisos_sistema as $key => $label): 
                $tiene = isset($permisos_activos[$key]) && $permisos_activos[$key] == true;
            ?>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="color: <?php echo $tiene ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: bold; font-size: 1.1rem;">
                        <?php echo $tiene ? '✅' : '❌'; ?>
                    </span>
                    <span style="font-size: 0.95rem; color: <?php echo $tiene ? 'var(--text)' : 'var(--text-light)'; ?>; text-decoration: <?php echo $tiene ? 'none' : 'line-through'; ?>;">
                        <?php echo $label; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function abrirModalRol(rolData = null) {
    const isEdit = rolData !== null;
    let htmlPermisos = '';
    const permisosSistema = <?php echo json_encode($lista_permisos_sistema); ?>;
    const permisosActivos = isEdit ? JSON.parse(rolData.permisos_json) : {};

    for (const [key, label] of Object.entries(permisosSistema)) {
        const checked = permisosActivos[key] ? 'checked' : '';
        htmlPermisos += `
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px 10px; border-radius: 6px; transition: 0.2s; margin-bottom: 5px;">
                <input type="checkbox" class="chk-permiso" value="${key}" ${checked} style="width: 22px; height: 22px; cursor: pointer;"> 
                <span style="font-size: 1rem;">${label}</span>
            </label>
        `;
    }

    Swal.fire({
        title: isEdit ? 'Editar Rol' : 'Crear Nuevo Rol',
        html: `
            <div class="text-start">
                <input type="hidden" id="swal_r_id" value="${isEdit ? rolData.id : ''}">
                <div class="form-group">
                    <label style="font-weight: 600;">Nombre del Rol</label>
                    <input type="text" id="swal_r_nombre" class="form-control" value="${isEdit ? rolData.nombre_rol : ''}" placeholder="Ej: Auditor, Padre...">
                </div>
                <hr style="margin: 20px 0;">
                <label style="font-weight: 700; margin-bottom: 15px; display: block;">Permisos:</label>
                <div style="max-height: 300px; overflow-y: auto;">
                    ${htmlPermisos}
                </div>
            </div>
        `,
        width: '550px',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nombre = Swal.getPopup().querySelector('#swal_r_nombre').value.trim();
            const id = Swal.getPopup().querySelector('#swal_r_id').value;
            if (!nombre) { Swal.showValidationMessage('El nombre del rol es obligatorio'); return false; }
            let permisos = {};
            Swal.getPopup().querySelectorAll('.chk-permiso').forEach(chk => { permisos[chk.value] = chk.checked; });
            return { action: 'guardar', id: id, nombre_rol: nombre, permisos_json: JSON.stringify(permisos) };
        }
    }).then((result) => {
        if (result.isConfirmed) procesarRol(result.value);
    });
}
function editarRol(rolData) { abrirModalRol(rolData); }
function eliminarRol(id, nombre) {
    Swal.fire({ title: `¿Eliminar rol ${nombre}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Sí, eliminar' })
    .then((result) => { if (result.isConfirmed) procesarRol({ action: 'eliminar', id: id }); });
}
function procesarRol(datos) {
    let fd = new FormData();
    for (let key in datos) fd.append(key, datos[key]);
    fetch('ajax_roles.php', { method: 'POST', body: fd }).then(res => res.json())
    .then(data => { if(data.status === 'success') location.reload(); else Swal.fire('Error', data.message, 'error'); });
}
</script>
<?php include 'includes/footer.php'; ?>