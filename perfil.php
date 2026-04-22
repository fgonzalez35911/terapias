<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

include 'includes/header.php';
$id_usuario = $_SESSION['usuario_id'];

// Pedimos todos los datos a la BD
$stmt = $pdo->prepare("SELECT nombre_completo, dni, firma_digital FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario]);
$user = $stmt->fetch();
?>

<div class="container-fluid py-4" style="background-color: #f4f7f6; min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h2 class="mb-4" style="font-weight: 700; color: #2c3e50; border-bottom: 3px solid #007bff; display: inline-block; padding-bottom: 10px;">Gestión de Perfil</h2>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4" style="color: #495057; font-weight: 600;">Información Personal</h4>
                            <form id="formPerfil">
                                <div class="form-group mb-3">
                                    <label class="form-label text-muted small uppercase" style="font-weight: 700;">Nombre Completo</label>
                                    <input type="text" id="p_nombre" class="form-control form-control-lg" style="border-radius: 10px; background-color: #f8f9fa;" value="<?php echo htmlspecialchars($user['nombre_completo'] ?? ''); ?>">
                                </div>
                                <div class="form-group mb-4">
                                    <label class="form-label text-muted small" style="font-weight: 700;">DNI (Documento de Identidad)</label>
                                    <input type="text" id="p_dni" class="form-control form-control-lg" style="border-radius: 10px; background-color: #f8f9fa;" value="<?php echo htmlspecialchars($user['dni'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-3" style="border-radius: 10px; font-weight: 600; letter-spacing: 1px; box-shadow: 0 4px 12px rgba(0,123,255,0.24);">
                                    ACTUALIZAR DATOS
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-body p-4 text-center">
                            <h4 class="card-title mb-4 text-start" style="color: #495057; font-weight: 600;">Firma Digital Autenticada</h4>
                            
                            <div id="previewFirma" class="mb-4 d-flex align-items-center justify-content-center" 
                                 style="border: 2px dashed #d1d8e0; border-radius: 15px; min-height: 180px; background-color: #ffffff; cursor: pointer; transition: all 0.3s;"
                                 onclick="abrirModalFirma()">
                                <?php if(!empty($user['firma_digital'])): ?>
                                    <img src="<?php echo $user['firma_digital']; ?>" style="max-height: 140px; max-width: 90%; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                                <?php else: ?>
                                    <div class="text-muted">
                                        <i class="fas fa-pen-nib fa-3x mb-3" style="color: #007bff;"></i>
                                        <p class="mb-0" style="font-weight: 500;">Presione para configurar firma</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn btn-outline-primary w-100 py-3" style="border-radius: 10px; font-weight: 600; border-width: 2px;" onclick="abrirModalFirma()">
                                <i class="fas fa-signature me-2"></i> CONFIGURAR NUEVA FIRMA
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formPerfil').addEventListener('submit', function(e) {
    e.preventDefault();
    let fd = new FormData();
    fd.append('accion', 'actualizar_datos');
    fd.append('nombre', document.getElementById('p_nombre').value);
    fd.append('dni', document.getElementById('p_dni').value);
    
    fetch('ajax_perfil.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => { 
        if(data.status === 'success') Swal.fire('Éxito', 'Perfil actualizado', 'success'); 
        else Swal.fire('Error', data.message, 'error');
    });
});

// CÓDIGO EXACTO DE TU OTRO SISTEMA - MEJORADO PARA MÓVILES
function abrirModalFirma() {
    Swal.fire({
        title: 'AUTENTICACIÓN DE FIRMA',
        width: '100vw',
        padding: '0',
        margin: '0',
        showConfirmButton: false,
        showCloseButton: true,
        closeButtonHtml: '<span style="color: white; font-size: 30px;">&times;</span>',
        customClass: {
            container: 'swal-full-screen',
            popup: 'swal-popup-full'
        },
        html: `
            <style>
                .swal-popup-full { border-radius: 0 !important; height: 100dvh !important; width: 100vw !important; margin: 0 !important; padding: 0 !important; overflow: hidden !important; }
                .signature-wrapper { background: #2c3e50; height: 100dvh; display: flex; flex-direction: column; width: 100vw; margin: 0; padding: 0; }
                .signature-header { padding: 15px 5px; color: white; text-align: center; background: #1a252f; flex-shrink: 0; }
                .canvas-container { flex-grow: 1; position: relative; margin: 0; padding: 0; background: white; overflow: hidden; width: 100vw; }
                .guide-line { position: absolute; bottom: 45%; left: 0; right: 0; border-bottom: 2px dashed #b2bec3; pointer-events: none; z-index: 1; }
                .guide-text { position: absolute; bottom: calc(45% - 20px); width: 100%; text-align: center; color: #b2bec3; font-size: 12px; font-family: sans-serif; letter-spacing: 2px; pointer-events: none; z-index: 1; }
                .signature-footer { padding: 10px; background: #1a252f; display: flex; gap: 10px; flex-shrink: 0; }
                .btn-signature { flex: 1; height: 60px; border: none; border-radius: 5px; font-weight: 700; font-size: 14px; text-transform: uppercase; cursor: pointer; transition: 0.2s; }
                .btn-clear { background: #e74c3c; color: white; }
                .btn-save { background: #27ae60; color: white; }
                #signature-pad { position: absolute; left: 0; top: 0; width: 100%; height: 100%; z-index: 10; touch-action: none; cursor: crosshair; }
            </style>
            <div class="signature-wrapper">
                <div class="signature-header">
                    <h3 style="margin:0; font-size: 18px;">PANEL DE FIRMA DIGITAL</h3>
                    <small style="opacity: 0.7;">Para mejor precisión, gire su dispositivo horizontalmente</small>
                </div>
                
                <div class="canvas-container">
                    <div class="guide-line"></div>
                    <div class="guide-text">ÁREA DE FIRMA</div>
                    
                    <canvas id="signature-pad"></canvas>
                </div>

                <div class="signature-footer">
                    <button class="btn-signature btn-clear" id="clear-signature">
                        REINICIAR
                    </button>
                    <button class="btn-signature btn-save" id="save-signature">
                        CONFIRMAR FIRMA
                    </button>
                </div>
            </div>
        `,
        didOpen: () => {
            const canvas = document.getElementById('signature-pad');
            const container = canvas.parentElement;

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = container.offsetWidth * ratio;
                canvas.height = container.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                if (window.signaturePad) window.signaturePad.clear();
            }

            window.addEventListener("resize", resizeCanvas);
            setTimeout(resizeCanvas, 100);

            window.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: '#000000',
                minWidth: 2,
                maxWidth: 4.5,
                velocityFilterWeight: 0.6
            });

            document.getElementById('clear-signature').addEventListener('click', () => window.signaturePad.clear());

            document.getElementById('save-signature').addEventListener('click', () => {
                if (window.signaturePad.isEmpty()) {
                    Swal.showValidationMessage('Debe ingresar una firma válida');
                    return;
                }

                const base64 = window.signaturePad.toDataURL('image/png');
                Swal.showLoading();

                let fd = new FormData();
                fd.append('accion', 'guardar_firma');
                fd.append('firma_b64', base64);
                
                fetch('ajax_perfil.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Firma Vinculada', timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error de Sistema', data.message, 'error');
                    }
                });
            });
        }
    });
}
Swal.fire('Error de Sistema', data.message, 'error');
                    }
                });
            });
        }
    });
}


</script>
<?php include 'includes/footer.php'; ?>
