<?php
session_start();
require_once 'db.php';
require_once 'includes/roles.php';

verificarAcceso('reportes');
include 'includes/header.php';

$total_reintegros = 0;
$total_aprobados = 0;
$total_pendientes = 0;
$data_meses = "[]";
$data_estados = "[]";

$vision_global = tienePermiso('ver_todo');
$filtro_base = $vision_global ? "" : " WHERE usuario_id = " . (int)$_SESSION['usuario_id'];
$filtro_and = $vision_global ? "" : " AND usuario_id = " . (int)$_SESSION['usuario_id'];

try {
    // Totales rápidos
    $total_reintegros = $pdo->query("SELECT COUNT(*) FROM reintegros" . $filtro_base)->fetchColumn();
    // Aprobados (Todo lo que sea Recibido o Pagado)
    $total_aprobados = $pdo->query("SELECT COUNT(*) FROM reintegros WHERE estado IN ('Recibido', 'Pagado Parcial', 'Pagado Total')" . $filtro_and)->fetchColumn();
    // Pendientes (Aún no enviados)
    $total_pendientes = $pdo->query("SELECT COUNT(*) FROM reintegros WHERE estado IN ('Pendiente', 'Firmado')" . $filtro_and)->fetchColumn();

    // Agrupación para gráficos (meses)
    $meses_db = $pdo->query("SELECT mes_correspondiente, COUNT(*) as cantidad FROM reintegros WHERE mes_correspondiente IS NOT NULL" . $filtro_and . " GROUP BY mes_correspondiente ORDER BY mes_correspondiente ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
    $meses_nombres = [1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic'];
    
    $labels_meses = [];
    $data_meses_arr = [];
    $numeros_meses = []; // Para el click del gráfico
    foreach($meses_db as $mes_num => $cant) {
        $labels_meses[] = $meses_nombres[$mes_num];
        $data_meses_arr[] = $cant;
        $numeros_meses[] = $mes_num;
    }

    // Agrupación para gráficos (estados)
    $estados_db = $pdo->query("SELECT IFNULL(estado, 'Pendiente') as estado, COUNT(*) as cantidad FROM reintegros" . $filtro_base . " GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

    $data_meses = json_encode([
        'labels' => $labels_meses,
        'data' => $data_meses_arr,
        'mes_id' => $numeros_meses
    ]);
    
    $data_estados = json_encode([
        'labels' => array_keys($estados_db),
        'data' => array_values($estados_db)
    ]);
} catch (Exception $e) {
    // Evita romper la página si la db falla
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .grafico-card { background: white; padding: 20px; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); height: 100%; transition: 0.3s; }
    .grafico-card:hover { box-shadow: var(--shadow-md); }
    .stat-clickable { cursor: pointer; transition: transform 0.2s; }
    .stat-clickable:hover { transform: translateY(-5px); }
</style>

<div class="flex-between">
    <h2>Reportes y Gráficos</h2>
    <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print me-2"></i> Imprimir Reporte</button>
</div>

<div class="grid-3">
    <div class="card stat-card stat-clickable" style="border-left-color: var(--primary);" onclick="location.href='historial.php'" title="Ver todos">
        <h3>Documentos Cargados</h3>
        <p><?php echo $total_reintegros; ?></p>
    </div>
    <div class="card stat-card success-border stat-clickable" onclick="location.href='historial.php?estado=Recibido'" title="Ver aprobados">
        <h3>Aprobados (IOSFA)</h3>
        <p><?php echo $total_aprobados; ?></p>
    </div>
    <div class="card stat-card danger-border stat-clickable" onclick="location.href='historial.php?estado=Pendiente'" title="Ver pendientes">
        <h3>Pendientes de Gestión</h3>
        <p><?php echo $total_pendientes; ?></p>
    </div>
</div>

<div class="grid-3" style="grid-template-columns: 2fr 1fr;">
    <div class="grafico-card">
        <h4 style="margin-bottom: 5px; color: var(--primary);">Cargas por Mes</h4>
        <p class="small text-muted mb-3">Toca una barra para filtrar el historial por mes.</p>
        <canvas id="barChart" height="100" style="cursor: pointer;"></canvas>
    </div>

    <div class="grafico-card">
        <h4 style="margin-bottom: 5px; color: var(--primary);">Distribución de Estados</h4>
        <p class="small text-muted mb-3">Toca una porción para filtrar.</p>
        <canvas id="pieChart" height="200" style="cursor: pointer;"></canvas>
    </div>
</div>

<script>
    const mesesData = <?php echo $data_meses; ?>;
    const estadosData = <?php echo $data_estados; ?>;
    const colores = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

    if(mesesData.labels && mesesData.labels.length > 0) {
        const barCtx = document.getElementById('barChart');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: mesesData.labels,
                datasets: [{
                    label: 'Documentos Subidos',
                    data: mesesData.data,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    borderRadius: 6,
                    hoverBackgroundColor: '#1d4ed8'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                onClick: (e) => {
                    const elements = barChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const mesId = mesesData.mes_id[index]; // Obtiene el número del mes (1, 2, 3...)
                        window.location.href = `historial.php?mes=${mesId}`;
                    }
                }
            }
        });
    }

    if(estadosData.labels && estadosData.labels.length > 0) {
        const pieCtx = document.getElementById('pieChart');
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: estadosData.labels,
                datasets: [{
                    data: estadosData.data,
                    backgroundColor: colores,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: { 
                responsive: true, 
                cutout: '65%',
                plugins: { legend: { position: 'bottom' } },
                onClick: (e) => {
                    const elements = pieChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const estadoClick = estadosData.labels[index];
                        window.location.href = `historial.php?estado=${estadoClick}`;
                    }
                }
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>