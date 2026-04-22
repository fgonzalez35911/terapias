<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('notificaciones');
include 'includes/header.php';
?>

<div class="flex-between">
    <h2>Centro de Mensajes y Alertas</h2>
    <div class="btn-group">
        <button class="btn btn-secondary" onclick="marcarTodasLeidas()">Leídas</button>
        <button class="btn btn-danger" onclick="limpiarNotificaciones()">Vaciar</button>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div id="lista-notificaciones" style="min-height: 400px;">
        </div>
</div>

<script>
function cargarNotificacionesFull() {
    fetch('ajax_notificaciones.php?action=listado_completo')
    .then(r => r.json())
    .then(data => {
        const contenedor = document.getElementById('lista-notificaciones');
        if (data.length === 0) {
            contenedor.innerHTML = '<div class="text-center p-5"><i class="fas fa-bell-slash fa-4x opacity-20"></i><p class="mt-3">Todo al día. No hay alertas nuevas.</p></div>';
            return;
        }
        
        let html = '';
        data.forEach(n => {
            const esNueva = n.leida == 0 ? 'border-left: 4px solid var(--secondary); background: #f0f9ff;' : 'opacity: 0.8;';
            html += `
            <div class="notif-item" style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; ${esNueva}">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <div style="font-size: 1.5rem;">${getIcon(n.tipo)}</div>
                    <div>
                        <h4 style="margin:0; font-size: 1rem;">${n.titulo}</h4>
                        <p style="margin:0; color: var(--text-light); font-size: 0.9rem;">${n.mensaje}</p>
                        <small style="color: #94a3b8;">${n.fecha}</small>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="${n.enlace}" class="btn btn-primary" onclick="marcarLeida(${n.id})" style="padding: 5px 15px; font-size: 0.8rem;">Ver detalle</a>
                    <button class="btn btn-danger" onclick="borrarNotif(${n.id})" style="padding: 5px 10px;">✕</button>
                </div>
            </div>`;
        });
        contenedor.innerHTML = html;
    });
}

function getIcon(tipo) {
    if(tipo == 'success') return '✅';
    if(tipo == 'danger') return '🚨';
    if(tipo == 'warning') return '⚠️';
    return '🔔';
}

function marcarLeida(id) { fetch(`ajax_notificaciones.php?action=marcar_leida&id=${id}`); }
function borrarNotif(id) { fetch(`ajax_notificaciones.php?action=borrar&id=${id}`).then(() => cargarNotificacionesFull()); }
function marcarTodasLeidas() { fetch('ajax_notificaciones.php?action=marcar_todas').then(() => cargarNotificacionesFull()); }
function limpiarNotificaciones() {
    Swal.fire({ title: '¿Borrar todo?', text: "Se vaciará tu bandeja de entrada.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Borrar todo' })
    .then((result) => { if (result.isConfirmed) fetch('ajax_notificaciones.php?action=limpiar_todo').then(() => cargarNotificacionesFull()); });
}

cargarNotificacionesFull();
</script>
<?php include 'includes/footer.php'; ?>