<?php 
require_once __DIR__ . '/roles.php'; 
if (!isset($_SESSION['alerta_login_mostrada'])) { $_SESSION['alerta_login_mostrada'] = false; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SGR - Sistema de Gestión</title>
    
    <link rel="manifest" href="/terapias/manifest.json">
    <meta name="theme-color" content="#0f172a">
    
    <link rel="icon" type="image/svg+xml" href="/terapias/logo_sgr.svg">
    <link rel="apple-touch-icon" href="/terapias/logo_sgr.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SGR">
    
    <link rel="stylesheet" href="/terapias/assets/css/style.css?v=12">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body>
    <?php if(strpos($_SERVER['SCRIPT_NAME'], 'login.php') === false): ?>
    
    <div class="menu-overlay" id="menuOverlay"></div>
    <div id="toast-container"></div>

    <nav class="navbar">
        <a href="index.php" class="logo-container" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
            <img src="/terapias/logo_sgr.svg" alt="SGR Logo" style="width: 42px; height: 42px; border-radius: 10px; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);">
            <h2 class="logo-text" style="font-weight: 700; color: #ffffff; letter-spacing: 1px; margin: 0; font-size: 1.5rem;">SGR</h2>
        </a>

        <div class="nav-links" id="navLinks">
            <div class="mobile-menu-header">
                <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="/terapias/logo_sgr.svg" alt="SGR Logo" style="width: 32px; height: 32px; border-radius: 8px;">
                    <span class="logo-text" style="color:white; margin:0; font-weight: 700; font-size: 1.2rem;">SGR</span>
                </a>
                <button class="close-menu" id="closeMenu">✕</button>
            </div>

            <a href="index.php" class="nav-item"><i class="fas fa-home me-2"></i> Inicio</a>

            <?php if(tienePermiso('subir_documentos') || tienePermiso('gestionar_terapeutas')): ?>
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn"><i class="fas fa-folder-open me-2"></i> Gestión <i class="fas fa-chevron-down ms-auto"></i></button>
                <div class="nav-dropdown-content">
                    <?php if(tienePermiso('subir_documentos')): ?>
                        <a href="subir_documentos.php" class="nav-item"><i class="fas fa-file-upload me-2"></i> Subir Docs</a>
                    <?php endif; ?>
                    <?php if(tienePermiso('gestionar_terapeutas')): ?>
                        <a href="terapeutas.php" class="nav-item"><i class="fas fa-user-md me-2"></i> Terapeutas</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if(tienePermiso('historial') || tienePermiso('reportes')): ?>
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn"><i class="fas fa-chart-line me-2"></i> Análisis <i class="fas fa-chevron-down ms-auto"></i></button>
                <div class="nav-dropdown-content">
                    <?php if(tienePermiso('historial')): ?>
                        <a href="historial.php" class="nav-item"><i class="fas fa-history me-2"></i> Historial</a>
                    <?php endif; ?>
                    <?php if(tienePermiso('reportes')): ?>
                        <a href="reportes.php" class="nav-item"><i class="fas fa-chart-bar me-2"></i> Reportes</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if(tienePermiso('notificaciones') || tienePermiso('ayuda')): ?>
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn">
                    <i class="fas fa-life-ring me-2"></i> Soporte 
                    <span id="badge-contador-menu" class="badge bg-danger ms-1" style="display:none; font-size:0.7rem; border-radius:50%; padding:2px 5px;">0</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </button>
                <div class="nav-dropdown-content">
                    <?php if(tienePermiso('notificaciones')): ?>
                        <a href="notificaciones.php" class="nav-item"><i class="fas fa-bell me-2"></i> Avisos</a>
                    <?php endif; ?>
                    <?php if(tienePermiso('ayuda')): ?>
                        <a href="ayuda.php" class="nav-item"><i class="fas fa-question-circle me-2"></i> Ayuda</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if(tienePermiso('configuracion_sistema')): ?>
            <div class="nav-dropdown">
                <button class="nav-dropdown-btn"><i class="fas fa-cogs me-2"></i> Sistema <i class="fas fa-chevron-down ms-auto"></i></button>
                <div class="nav-dropdown-content">
                    <a href="usuarios.php" class="nav-item"><i class="fas fa-users me-2"></i> Usuarios</a>
                    <a href="gestionar_roles.php" class="nav-item" style="color: var(--warning);"><i class="fas fa-user-shield me-2"></i> Roles</a>
                    <a href="configuracion.php" class="nav-item"><i class="fas fa-sliders-h me-2"></i> Ajustes</a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="nav-divider"></div>
            <a href="perfil.php" class="nav-item"><i class="fas fa-user-circle me-2"></i> Mi Perfil</a>
            <a href="logout.php" class="nav-item btn-salir"><i class="fas fa-sign-out-alt me-2"></i> Salir</a>
        </div>

        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
    </nav>

    <script>
        // 1. REGISTRO DEL SERVICE WORKER (VITAL PARA QUE FUNCIONE LA APP)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/terapias/sw.js')
                .then(reg => console.log('SW Activado y listo para PWA'))
                .catch(err => console.log('Error de SW:', err));
            });
        }

        // 2. CAPTURADOR DE INSTALACIÓN Y POPUP AUTOMÁTICO
        let promptInstalacion;
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevenimos que Chrome lance la barra pequeña abajo
            e.preventDefault();
            promptInstalacion = e;
            
            // Si el usuario ya lo cerró en esta sesión, no lo molestamos más
            if (!sessionStorage.getItem('cartel_instalacion_visto')) {
                // Le damos 2 segundos para que la página cargue y luego le disparamos el cartel
                setTimeout(() => {
                    Swal.fire({
                        title: '¡Instalá la App SGR!',
                        text: 'Agregá el sistema a tu pantalla de inicio para entrar directo, sin el navegador y a pantalla completa.',
                        imageUrl: '/terapias/logo_sgr.svg',
                        imageWidth: 80,
                        imageHeight: 80,
                        showCancelButton: true,
                        confirmButtonColor: '#3b82f6',
                        cancelButtonColor: '#94a3b8',
                        confirmButtonText: '📲 Instalar App',
                        cancelButtonText: 'Ahora no',
                        customClass: { popup: 'rounded-4' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Si acepta, mostramos el instalador real de Chrome/Android
                            promptInstalacion.prompt();
                            promptInstalacion.userChoice.then((choiceResult) => {
                                promptInstalacion = null;
                            });
                        }
                        sessionStorage.setItem('cartel_instalacion_visto', 'true');
                    });
                }, 2000);
            }
        });

        // ==========================================
        // LÓGICA DEL MENÚ Y NOTIFICACIONES
        // ==========================================
        const toggleMenu = () => { 
            document.getElementById('navLinks').classList.toggle('active'); 
            document.getElementById('menuOverlay').classList.toggle('active'); 
        };
        document.getElementById('menuToggle').addEventListener('click', toggleMenu);
        document.getElementById('closeMenu').addEventListener('click', toggleMenu);
        document.getElementById('menuOverlay').addEventListener('click', toggleMenu);

        document.querySelectorAll('.nav-dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if(window.innerWidth <= 1100) {
                    e.preventDefault();
                    this.parentElement.classList.toggle('active');
                    const icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');
                    if(icon) {
                        icon.classList.toggle('fa-chevron-down');
                        icon.classList.toggle('fa-chevron-up');
                    }
                }
            });
        });

        let alertaLoginMostrada = <?php echo $_SESSION['alerta_login_mostrada'] ? 'true' : 'false'; ?>;

        function checkNotificaciones() {
            fetch('ajax_notificaciones.php?action=check')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('badge-contador-menu');
                if (data.total_sin_leer > 0) {
                    badge.style.display = 'inline-block';
                    badge.innerText = data.total_sin_leer;
                } else { badge.style.display = 'none'; }

                if (data.total_sin_leer > 0 && !alertaLoginMostrada) {
                    Swal.fire({ 
                        title: '¡Tienes Notificaciones!', 
                        text: `Tienes ${data.total_sin_leer} avisos pendientes de revisar.`, 
                        icon: 'info', 
                        confirmButtonText: 'Ver ahora',
                        toast: true,
                        position: 'top-end',
                        timer: 5000
                    }).then((result) => {
                        if (result.isConfirmed) { window.location.href = 'notificaciones.php'; }
                    });
                    fetch('ajax_notificaciones.php?action=marcar_alerta_login');
                    alertaLoginMostrada = true;
                }

                if (data.nuevas_en_vivo.length > 0) {
                    data.nuevas_en_vivo.forEach(notif => {
                        let div = document.createElement('div');
                        div.className = `toast-msg ${notif.tipo}`;
                        div.onclick = () => window.location.href = notif.enlace;
                        div.innerHTML = `
                            <div class="toast-icon"><i class="fas fa-bell"></i></div>
                            <div class="toast-content">
                                <h4>${notif.titulo}</h4>
                                <p>${notif.mensaje}</p>
                            </div>
                        `;
                        document.getElementById('toast-container').appendChild(div);
                        setTimeout(() => {
                            div.style.animation = 'fadeOut 0.4s forwards';
                            setTimeout(() => div.remove(), 400);
                        }, 8000);
                    });
                }
            });
        }
        setInterval(checkNotificaciones, 10000);
        setTimeout(checkNotificaciones, 1000);
    </script>
    <div class="container">
    <?php endif; ?>