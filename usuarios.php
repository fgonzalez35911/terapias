<?php 
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('configuracion_sistema');

include 'includes/header.php'; 

$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nombre_completo ASC");
$lista_usuarios = $stmt->fetchAll();

// Obtener roles reales de la base de datos para no tener NADA hardcodeado
$roles_db = $pdo->query("SELECT nombre_rol FROM roles_dinamicos ORDER BY nombre_rol ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="flex-between">
    <h2>Gestión de Usuarios y Roles</h2>
    <button class="btn btn-primary" onclick="abrirModalUsuario()">+ Nuevo Usuario</button>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario (Login)</th>
                <th>Rol</th>
                <th>Email / Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($lista_usuarios as $u): ?>
            <tr>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                <td>
                    <span class="badge" style="background: var(--secondary); color: white; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">
                        <?php echo htmlspecialchars($u['rol']); ?>
                    </span>
                </td>
                <td class="small text-muted">
                    <?php echo htmlspecialchars($u['email'] ?? 'Sin email'); ?><br>
                    <?php echo htmlspecialchars($u['telefono'] ?? 'Sin teléfono'); ?>
                </td>
                <td>
                    <button class="btn btn-primary" style="padding: 4px 10px; font-size: 0.8rem;" onclick='editarUsuario(<?php echo json_encode($u); ?>)'>Editar</button>
                    <button class="btn btn-warning" style="padding: 4px 10px; font-size: 0.8rem; color: black;" onclick="resetearClave(<?php echo $u['id']; ?>)">Clave</button>
                    <?php if($u['id'] != $_SESSION['usuario_id']): ?>
                        <button class="btn btn-danger" style="padding: 4px 10px; font-size: 0.8rem;" onclick="eliminarUsuario(<?php echo $u['id']; ?>)">✕</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="molde_formulario_usuario" class="hidden">
    <div class="text-start">
        <input type="hidden" id="swal_u_id" value="">
        
        <div class="grid-3" style="grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Nombre Completo *</label>
                <input type="text" id="swal_u_nombre" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">DNI</label>
                <input type="text" id="swal_u_dni" class="form-control">
            </div>
        </div>

        <div class="grid-3" style="grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Usuario (Para login) *</label>
                <input type="text" id="swal_u_usuario" class="form-control">
            </div>
            <div class="form-group" id="caja_pass" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Contraseña Inicial</label>
                <input type="text" id="swal_u_pass" class="form-control" value="sgr1234">
            </div>
        </div>

        <div class="grid-3" style="grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Rol en el Sistema *</label>
                <select id="swal_u_rol" class="form-control" style="text-transform: uppercase;">
                    <?php foreach($roles_db as $rol): ?>
                        <option value="<?php echo htmlspecialchars($rol); ?>"><?php echo htmlspecialchars($rol); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Género</label>
                <select id="swal_u_genero" class="form-control">
                    <option value="otro">Otro</option>
                    <option value="masculino">Masculino</option>
                    <option value="femenino">Femenino</option>
                </select>
            </div>
        </div>

        <div class="grid-3" style="grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Email</label>
                <input type="email" id="swal_u_email" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom: 5px;">
                <label style="font-weight: 600; font-size: 0.85rem;">Teléfono</label>
                <input type="text" id="swal_u_telefono" class="form-control">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 5px;">
            <label style="font-weight: 600; font-size: 0.85rem;">Fecha de Nacimiento</label>
            <input type="date" id="swal_u_fecha_nac" class="form-control">
        </div>
    </div>
</div>

<script>
function abrirModalUsuario(u = null) {
    const isEdit = u !== null;
    let molde = document.getElementById('molde_formulario_usuario').innerHTML;

    Swal.fire({
        title: isEdit ? 'Editar Usuario' : 'Crear Nuevo Usuario',
        html: molde,
        width: '650px',
        showCancelButton: true,
        confirmButtonText: 'Guardar Datos',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const popup = Swal.getPopup();
            if (isEdit) {
                popup.querySelector('#swal_u_id').value = u.id;
                popup.querySelector('#swal_u_nombre').value = u.nombre_completo || '';
                popup.querySelector('#swal_u_dni').value = u.dni || '';
                
                let inputUsuario = popup.querySelector('#swal_u_usuario');
                inputUsuario.value = u.usuario;
                inputUsuario.disabled = true; // No dejamos cambiar el user de login
                
                popup.querySelector('#caja_pass').style.display = 'none'; // Ocultamos clave en edición
                popup.querySelector('#swal_u_rol').value = u.rol;
                popup.querySelector('#swal_u_genero').value = u.genero || 'otro';
                popup.querySelector('#swal_u_email').value = u.email || '';
                popup.querySelector('#swal_u_telefono').value = u.telefono || '';
                popup.querySelector('#swal_u_fecha_nac').value = u.fecha_nacimiento || '';
            }
        },
        preConfirm: () => {
            const popup = Swal.getPopup();
            const nombre = popup.querySelector('#swal_u_nombre').value.trim();
            const usuario = popup.querySelector('#swal_u_usuario').value.trim();
            
            if (!nombre || !usuario) {
                Swal.showValidationMessage('Nombre y Usuario son campos obligatorios');
                return false;
            }
            
            const data = {
                action: 'guardar',
                id: popup.querySelector('#swal_u_id').value,
                nombre: nombre,
                usuario: usuario,
                rol: popup.querySelector('#swal_u_rol').value,
                dni: popup.querySelector('#swal_u_dni').value.trim(),
                email: popup.querySelector('#swal_u_email').value.trim(),
                telefono: popup.querySelector('#swal_u_telefono').value.trim(),
                genero: popup.querySelector('#swal_u_genero').value,
                fecha_nacimiento: popup.querySelector('#swal_u_fecha_nac').value
            };
            
            if (!isEdit) {
                data.pass = popup.querySelector('#swal_u_pass').value;
            }
            return data;
        }
    }).then((result) => {
        if (result.isConfirmed) enviarDataUsuario(result.value);
    });
}

function editarUsuario(u) { abrirModalUsuario(u); }

function resetearClave(id) {
    Swal.fire({
        title: 'Nueva Contraseña',
        input: 'text',
        inputValue: 'sgr1234',
        showCancelButton: true,
        confirmButtonText: 'Cambiar Clave',
        inputValidator: (value) => { if (!value.trim()) return 'Debes escribir una clave'; }
    }).then((result) => {
        if (result.isConfirmed) enviarDataUsuario({ action: 'reset_pass', id: id, pass: result.value });
    });
}

function eliminarUsuario(id) {
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) enviarDataUsuario({ action: 'eliminar', id: id });
    });
}

function enviarDataUsuario(datos) {
    let fd = new FormData();
    for (let key in datos) fd.append(key, datos[key]);

    fetch('ajax_usuarios.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire('¡Listo!', 'Operación exitosa.', 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>