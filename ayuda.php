<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('ayuda');
include 'includes/header.php';
?>

<div class="flex-between">
    <h2>Centro de Ayuda y Soporte</h2>
</div>

<div class="grid-3">
    <div class="card">
        <h3 style="color: var(--secondary);"><i class="fas fa-stethoscope me-2"></i> Para Terapeutas</h3>
        <ul style="padding-left: 20px; margin-top: 15px;">
            <li>Carguen la factura en formato PDF (Max 5MB).</li>
            <li>Asegúrense de que el mes y año coincidan con la sesión.</li>
            <li>El sistema avisará por mail cuando se procese el pago.</li>
        </ul>
    </div>

    <div class="card">
        <h3 style="color: var(--success);"><i class="fas fa-user-friends me-2"></i> Para Padres</h3>
        <ul style="padding-left: 20px; margin-top: 15px;">
            <li>Revisen la sección "Historial" para ver el estado en IOSFA.</li>
            <li>Las firmas digitales se aplican automáticamente al subir.</li>
            <li>Debemos enviar todo antes del día 10 de cada mes.</li>
        </ul>
    </div>

    <div class="card">
        <h3><i class="fas fa-headset me-2"></i> Soporte Técnico</h3>
        <p class="small text-muted mt-2">¿Problemas con el sistema o el PDF?</p>
        <div class="d-grid gap-2 mt-3">
            <a href="mailto:soporte@federicogonzalez.net" class="btn btn-primary btn-full">Enviar Email</a>
            <a href="https://wa.me/54911XXXXXXXX" target="_blank" class="btn btn-success btn-full">WhatsApp Directo</a>
        </div>
    </div>
</div>

<div class="card mt-4">
    <h3>Preguntas Frecuentes (FAQ)</h3>
    <div style="margin-top: 20px;">
        <details style="padding: 15px; border-bottom: 1px solid var(--border); cursor: pointer;">
            <summary style="font-weight: 600;">¿Qué hago si IOSFA rechaza un documento?</summary>
            <p class="mt-2 text-muted">Debes ir a Historial, ver el motivo del rechazo, y volver a subir el documento corregido. El sistema marcará la versión anterior como obsoleta.</p>
        </details>
        <details style="padding: 15px; border-bottom: 1px solid var(--border); cursor: pointer;">
            <summary style="font-weight: 600;">¿Cómo funciona la firma automática?</summary>
            <p class="mt-2 text-muted">Al cargar el PDF, el motor "Rayos Pro" estampa tu firma digital guardada en el perfil directamente en la última página del documento.</p>
        </details>
    </div>
</div>

<?php include 'includes/footer.php'; ?>