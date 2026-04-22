<?php 
session_start();
if(!isset($_SESSION['usuario_id'])) { header("Location: /terapias/login.php"); exit; }
require_once 'db.php';
include 'includes/header.php'; 

$stmt = $pdo->query("SELECT id, nombre FROM terapeutas WHERE activo = 1 ORDER BY nombre ASC");
$terapeutas = $stmt->fetchAll();

$stmtU = $pdo->prepare("SELECT nombre_completo, dni, firma_digital FROM usuarios WHERE id = ?");
$stmtU->execute([$_SESSION['usuario_id']]);
$user = $stmtU->fetch();
?>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

<style>
    /* Declaramos todas las variantes posibles que busca ARCA */
    @font-face { font-family: 'Arial'; src: url('fonts/ARIAL.TTF') format('truetype'); }
    @font-face { font-family: 'ArialMT'; src: url('fonts/ARIAL.TTF') format('truetype'); }
    @font-face { font-family: 'Arial-BoldMT'; src: url('fonts/ARIALBD.TTF') format('truetype'); }
    @font-face { font-family: 'CourierNewPSMT'; src: url('fonts/cour.ttf') format('truetype'); }

    .fullscreen-mode {
        position: fixed !important; top: 0 !important; left: 0 !important;
        width: 100vw !important; height: 100vh !important;
        z-index: 9998 !important; border-radius: 0 !important;
    }
</style>

<div id="pantalla_eleccion" class="container text-center" style="margin-top: 50px;">
    <h2 style="margin-bottom: 40px; font-weight: 800;">¿Qué documento vas a procesar?</h2>
    <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
        <div class="card btn-eleccion" onclick="seleccionarTipo('Factura')">
            <span class="emoji">🧾</span>
            <h3>Factura ARCA</h3>
        </div>
        <div class="card btn-eleccion" onclick="seleccionarTipo('Planilla')">
            <span class="emoji">📅</span>
            <h3>Planilla Asistencia</h3>
        </div>
    </div>
</div>

<div id="pantalla_editor" class="editor-layout hidden container">
    <div class="editor-sidebar card">
        <div class="flex-between" style="margin-bottom: 20px;">
            <h3 id="titulo_editor" style="color: var(--secondary); margin:0;"></h3>
            <button class="btn btn-danger" style="padding: 5px 10px;" onclick="location.reload()">Cambiar</button>
        </div>

        <div class="form-group">
            <label>Subir Archivo</label>
            <input type="file" id="archivo_upload" class="form-control" accept="application/pdf, image/*">
        </div>

        <hr style="margin: 20px 0;">

        <div id="controles_formulario" class="hidden">
            <div class="form-group">
                <label>Terapeuta</label>
                <select id="terapeuta_id" class="form-control">
                    <option value="">Seleccionar...</option>
                    <?php foreach($terapeutas as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Mes y Año</label>
                <div style="display:flex; gap:10px;">
                    <select id="mes" class="form-control"><?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".(($i==date('n'))?'selected':'').">$i</option>"; ?></select>
                    <input type="number" id="anio" class="form-control" value="<?php echo date('Y'); ?>">
                </div>
            </div>

            <div id="campos_ia_factura" class="hidden">
                <div class="form-group">
                    <label>Importe Total ($)</label>
                    <input type="number" id="monto_total" class="form-control" step="0.01" style="font-weight:bold; color:var(--success);">
                </div>
                <div class="form-group"><label>Paciente</label><input type="text" id="paciente_f" class="form-control"></div>
                <div class="form-group"><label>Prestación</label><input type="text" id="prestacion_f" class="form-control"></div>
                <div class="form-group"><label>Sesiones</label><input type="text" id="sesiones_f" class="form-control"></div>
                <div class="form-group"><label>Fecha Emisión</label><input type="text" id="fecha_f" class="form-control"></div>
            </div>

            <div id="campos_ia_planilla" class="hidden">
                <div class="form-group"><label>Beneficiario</label><input type="text" id="paciente_p" class="form-control"></div>
                <div class="form-group"><label>Prestación</label><input type="text" id="prestacion_p" class="form-control"></div>
            </div>

            <button class="btn btn-success btn-full" style="margin-top: 20px;" onclick="guardarYProcesar()">💾 Guardar Archivo</button>
        </div>
    </div>

    <div class="editor-main card" id="canvas_container" style="position: relative;">
        <div id="zoom_controls" style="position: absolute; top: 15px; left: 15px; z-index: 10; display: flex; flex-direction: column; gap: 8px;">
            <button class="btn btn-primary" style="padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); font-size: 20px;" onclick="zoomCanvas(1.2)">➕</button>
            <button class="btn btn-primary" style="padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); font-size: 20px;" onclick="zoomCanvas(0.8)">➖</button>
            <button class="btn btn-success" style="padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); font-size: 20px; margin-top: 5px;" onclick="resetZoom()">🔄</button>
        </div>
        <canvas id="editor_canvas"></canvas>
    </div>
</div>

<div id="panel_multifirma" class="hidden" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 10000; background: var(--primary); padding: 12px 20px; border-radius: 30px; box-shadow: var(--shadow-lg); display: flex; gap: 10px; align-items: center; border: 2px solid var(--secondary);">
    <span style="color: white; font-weight: 500; font-size: 0.95rem;" id="txt_multi_estado">Ajustá la firma y confirmá el tamaño</span>
    <button id="btn_confirmar_multi" class="btn btn-success" style="padding: 6px 15px; font-size: 0.85rem;" onclick="confirmarTamanoMulti()">✅ Confirmar</button>
    <button class="btn btn-danger" style="padding: 6px 15px; font-size: 0.85rem;" onclick="cancelarMulti()">❌ Cancelar</button>
</div>

<div id="fab_menu" class="fab-container hidden">
    <button class="fab-main" style="color: #ffffff; font-weight: 300; font-size: 40px; padding-bottom: 5px;" onclick="this.parentElement.classList.toggle('active')">+</button>
    <div class="fab-menu" id="fab_items_menu">
        <button class="fab-item" onclick="limpiarTodo()"><span class="fab-label">Resetear Todo</span><div class="fab-icon">🧹</div></button>
        <button class="fab-item" onclick="vistaPrevia()"><span class="fab-label">Vista Previa HD</span><div class="fab-icon">🔍</div></button>
        <button class="fab-item" onclick="togglePrecision()"><span id="lbl_precision" class="fab-label">Modo Precisión: OFF</span><div class="fab-icon">🎯</div></button>
        <button class="fab-item" onclick="eliminarSeleccion()"><span class="fab-label">Borrar Seleccionado</span><div class="fab-icon">🗑️</div></button>
        <button class="fab-item" onclick="toggleFullscreen()"><span id="lbl_fullscreen" class="fab-label">Pantalla Completa</span><div class="fab-icon">🔲</div></button>
        <button class="fab-item" onclick="agregarAclaracion()"><span class="fab-label">Aclaración</span><div class="fab-icon">🖋️</div></button>
        <button id="btn_firma_dinamico" class="fab-item" onclick="accionFirma()"><span id="lbl_firma" class="fab-label">Firmar</span><div class="fab-icon">📝</div></button>
    </div>
</div>

<input type="hidden" id="user_nombre" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>">
<input type="hidden" id="user_dni" value="<?php echo htmlspecialchars($user['dni']); ?>">

<script>
let canvas, modoMultiFirma = false, estadoMulti = 'inactivo', modoPrecision = false, isDragging = false, lastPosX = 0, lastPosY = 0, firmaGuardada = '<?php echo $user['firma_digital']; ?>', ultimaEscalaFirma = null;
let originalPdfBytes = null, esPdfOriginal = false, originalImageSrc = null;
const terapeutasDB = <?php echo json_encode($terapeutas); ?>;

function seleccionarTipo(tipo) {
    document.getElementById('pantalla_eleccion').style.display = 'none';
    document.getElementById('pantalla_editor').classList.remove('hidden');
    document.getElementById('titulo_editor').innerText = tipo;
    
    if(tipo === 'Factura') {
        document.getElementById('campos_ia_factura').classList.remove('hidden');
        document.getElementById('lbl_firma').innerText = 'Estampar Firma';
    } else {
        document.getElementById('campos_ia_planilla').classList.remove('hidden');
        document.getElementById('lbl_firma').innerText = 'Activar Multi-Firma';
    }
    
    // Apagamos el scroll táctil por defecto del navegador para que nosotros controlemos el movimiento
    canvas = new fabric.Canvas('editor_canvas', { allowTouchScrolling: false });

    // ESTILO GLOBAL DE SELECCIÓN (Bordes punteados sutiles y controles redondos chicos para que no molesten)
    fabric.Object.prototype.set({
        transparentCorners: false,
        cornerColor: '#ffffff', // Centro blanco
        cornerStrokeColor: '#3b82f6', // Borde azul
        borderColor: '#3b82f6', // Línea de selección azul
        cornerSize: 12, // Tamaño súper reducido
        padding: 8,
        cornerStyle: 'circle', // Controles redondos
        borderDashArray: [5, 5], // Línea punteada fantasma
        lockScalingFlip: true // Bloquea el efecto espejo en todos lados
    });
    
    canvas.on('mouse:down', function(opt) {
        let evt = opt.e;
        if (evt.touches && evt.touches.length > 1) return;

        if(modoMultiFirma && estadoMulti === 'estampando' && firmaGuardada && !opt.target) {
            // SOLUCIÓN CELULAR: getPointer calcula la coordenada exacta del PDF ignorando el scroll/zoom externo
            let pointer = canvas.getPointer(opt.e); 
            
            fabric.Image.fromURL(firmaGuardada, img => {
                if (ultimaEscalaFirma) {
                    img.set({ scaleX: ultimaEscalaFirma.scaleX, scaleY: ultimaEscalaFirma.scaleY });
                } else {
                    img.scaleToWidth(window.innerWidth < 768 ? 80 : 150); // Más chico de base si es celular
                }
                img.set({ 
                    left: pointer.x, top: pointer.y, originX: 'center', originY: 'center' 
                });
                canvas.add(img); canvas.setActiveObject(img);
            });
        } else if (!opt.target) {
            isDragging = true;
            canvas.selection = false;
            lastPosX = evt.clientX || (evt.touches ? evt.touches[0].clientX : opt.pointer.x);
            lastPosY = evt.clientY || (evt.touches ? evt.touches[0].clientY : opt.pointer.y);
        }
    });

    canvas.on('mouse:move', function(opt) {
        if (isDragging) {
            let e = opt.e;
            let clientX = e.clientX || (e.touches ? e.touches[0].clientX : opt.pointer.x);
            let clientY = e.clientY || (e.touches ? e.touches[0].clientY : opt.pointer.y);
            let vpt = canvas.viewportTransform;
            vpt[4] += clientX - lastPosX;
            vpt[5] += clientY - lastPosY;
            canvas.requestRenderAll();
            lastPosX = clientX;
            lastPosY = clientY;
        }
    });

    canvas.on('mouse:up', function(opt) {
        canvas.setViewportTransform(canvas.viewportTransform);
        isDragging = false;
        canvas.selection = true;
        
        // Borrar el conector del Modo Precisión si existe al soltar el dedo
        canvas.getObjects().filter(o => o.id === 'temp_connector').forEach(o => canvas.remove(o));
        canvas.requestRenderAll();
    });

    canvas.on('object:scaled', function(opt) {
        if (modoMultiFirma && estadoMulti === 'ajustando' && opt.target && opt.target.type === 'image') {
            ultimaEscalaFirma = { scaleX: opt.target.scaleX, scaleY: opt.target.scaleY };
        }
    });

    // Lógica del conector visual (El "Palito") ahora protegida adentro del canvas
    canvas.on('object:moving', function(e) {
        if (modoPrecision) {
            let obj = e.target;
            // El objeto se mantiene 80 pixeles arriba de la posicion real del puntero
            obj.top -= 80 / canvas.getZoom(); 
            
            // Dibujamos una linea temporal (conector)
            let pointer = canvas.getPointer(e.e);
            let line = new fabric.Line([pointer.x, pointer.y, obj.left, obj.top], {
                stroke: '#f97316',
                strokeWidth: 2,
                selectable: false,
                evented: false,
                strokeDashArray: [5, 5],
                id: 'temp_connector'
            });
            
            // Borrar conector anterior y poner el nuevo
            canvas.getObjects().filter(o => o.id === 'temp_connector').forEach(o => canvas.remove(o));
            canvas.add(line);
            line.sendToBack();
        }
    });
}

function accionFirma() {
    let vpt = canvas.viewportTransform;
    let zoom = canvas.getZoom();
    // Cálculo de centrado perfecto: evalúa lo que tus ojos ven en pantalla ahora mismo
    let cx = (canvas.getWidth() / 2 - vpt[4]) / zoom;
    let cy = (canvas.getHeight() / 2 - vpt[5]) / zoom;
    let esCelular = window.innerWidth < 768;

    if(document.getElementById('titulo_editor').innerText === 'Factura') {
        fabric.Image.fromURL(firmaGuardada, img => {
            img.scaleToWidth(esCelular ? 80 : 150); 
            img.set({ left: cx, top: cy, originX: 'center', originY: 'center' });
            canvas.add(img); canvas.setActiveObject(img);
        });
    } else {
        if(modoMultiFirma) {
            cancelarMulti();
        } else {
            modoMultiFirma = true;
            estadoMulti = 'ajustando';
            document.getElementById('lbl_firma').innerText = 'Cancelar Multi';
            document.getElementById('panel_multifirma').classList.remove('hidden');

            fabric.Image.fromURL(firmaGuardada, img => {
                if (ultimaEscalaFirma) {
                    img.set({ scaleX: ultimaEscalaFirma.scaleX, scaleY: ultimaEscalaFirma.scaleY });
                } else {
                    img.scaleToWidth(esCelular ? 80 : 150);
                }
                img.set({ left: cx, top: cy, originX: 'center', originY: 'center' });
                canvas.add(img); canvas.setActiveObject(img);
            });
        }
    }
    document.getElementById('fab_menu').classList.remove('active');
}

function confirmarTamanoMulti() {
    let actObj = canvas.getActiveObject();
    if (actObj && actObj.type === 'image') {
        ultimaEscalaFirma = { scaleX: actObj.scaleX, scaleY: actObj.scaleY };
    }
    
    estadoMulti = 'estampando';
    document.getElementById('txt_multi_estado').innerText = 'Modo Estampado Activo. Tocá la pantalla.';
    document.getElementById('btn_confirmar_multi').style.display = 'none';
    
    canvas.discardActiveObject();
    canvas.renderAll();
    
    Swal.fire({
        title: '¡Confirmado!',
        text: 'Tocá cualquier lugar del PDF para estampar la firma.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}

function cancelarMulti() {
    modoMultiFirma = false;
    estadoMulti = 'inactivo';
    document.getElementById('lbl_firma').innerText = 'Activar Multi-Firma';
    document.getElementById('panel_multifirma').classList.add('hidden');
    document.getElementById('txt_multi_estado').innerText = 'Ajustá la firma y confirmá el tamaño';
    document.getElementById('btn_confirmar_multi').style.display = 'inline-block';
}

function toggleFullscreen() {
    let editorMain = document.getElementById('canvas_container');
    let isFull = editorMain.classList.toggle('fullscreen-mode');
    
    if(isFull) {
        document.getElementById('lbl_fullscreen').innerText = 'Salir Pantalla Completa';
        document.body.style.overflow = 'hidden';
        
        // 1. Guardar el tamaño original del lienzo para cuando salgamos
        if (!canvas.origWidth) {
            canvas.origWidth = canvas.getWidth();
            canvas.origHeight = canvas.getHeight();
        }
        
        // 2. Expandir los píxeles físicos del lienzo al total de la pantalla del celular
        canvas.setWidth(window.innerWidth);
        canvas.setHeight(window.innerHeight);
        
        // 3. Auto-Zoom: Calcular la diferencia de tamaño para agrandar el PDF
        let ratio = window.innerWidth / canvas.origWidth;
        let nuevoZoom = ratio > 1 ? ratio * 0.95 : 1.05; // 0.95 deja un pequeño margen para que no toque los bordes
        canvas.setZoom(nuevoZoom);
        
        // 4. Centrar automáticamente el PDF en la nueva pantalla gigante
        let vpt = canvas.viewportTransform;
        vpt[4] = (window.innerWidth - (canvas.origWidth * nuevoZoom)) / 2;
        vpt[5] = 40; // Margen superior para que no quede pegado arriba
        
        Swal.fire({
            title: 'Pantalla Completa', 
            text: 'El PDF se agrandó para ocupar todo el celular. Podés seguir ajustando el Zoom con (+ y -) o moverte arrastrando el fondo.', 
            icon: 'success',
            timer: 3500,
            showConfirmButton: false
        });
    } else {
        document.getElementById('lbl_fullscreen').innerText = 'Pantalla Completa';
        document.body.style.overflow = '';
        
        // Restaurar exactamente el tamaño y zoom original al salir
        if (canvas.origWidth) {
            canvas.setWidth(canvas.origWidth);
            canvas.setHeight(canvas.origHeight);
            canvas.setZoom(1);
            
            let vpt = canvas.viewportTransform;
            vpt[4] = 0;
            vpt[5] = 0;
        }
    }
    document.getElementById('fab_menu').classList.remove('active');
    canvas.requestRenderAll();
    canvas.calcOffset();
}

function zoomCanvas(factor) {
    let zoom = canvas.getZoom() * factor;
    if (zoom > 5) zoom = 5;
    if (zoom < 0.2) zoom = 0.2;
    // Hacer zoom siempre apuntando al centro de lo que estás viendo actualmente
    let vpt = canvas.viewportTransform;
    let cx = (canvas.width / 2 - vpt[4]) / vpt[0];
    let cy = (canvas.height / 2 - vpt[5]) / vpt[3];
    canvas.zoomToPoint({ x: cx, y: cy }, zoom);
}

function resetZoom() {
    let editorMain = document.getElementById('canvas_container');
    if (editorMain.classList.contains('fullscreen-mode') && canvas.origWidth) {
        // Recalcula el tamaño exacto que tenía al entrar a pantalla completa
        let ratio = window.innerWidth / canvas.origWidth;
        let nuevoZoom = ratio > 1 ? ratio * 0.95 : 1.05;
        
        canvas.setZoom(nuevoZoom);
        
        let vpt = canvas.viewportTransform;
        vpt[4] = (window.innerWidth - (canvas.origWidth * nuevoZoom)) / 2;
        vpt[5] = 40;
    } else {
        // Si por algún motivo lo aprieta fuera de pantalla completa, vuelve al 100%
        canvas.setZoom(1);
        let vpt = canvas.viewportTransform;
        vpt[4] = 0;
        vpt[5] = 0;
    }
    canvas.requestRenderAll();
    canvas.calcOffset();
}

function agregarAclaracion() {
    let vpt = canvas.viewportTransform;
    let zoom = canvas.getZoom();
    let cx = (canvas.getWidth() / 2 - vpt[4]) / zoom;
    let cy = (canvas.getHeight() / 2 - vpt[5]) / zoom;
    let esCelular = window.innerWidth < 768;

    let t = new fabric.Text(`${document.getElementById('user_nombre').value}\nDNI: ${document.getElementById('user_dni').value}`, {
        left: cx, top: cy, originX: 'center', originY: 'center', 
        fontSize: esCelular ? 12 : 20,
        textAlign: 'center',
        fontFamily: 'Arial-BoldMT'
    });
    canvas.add(t); canvas.setActiveObject(t);
    document.getElementById('fab_menu').classList.remove('active');

    // Efecto de resplandor naranja detrás de la letra (titila sin hacer desaparecer el texto)
    let parpadeos = 0;
    let brilloNaranja = new fabric.Shadow({ color: '#f97316', blur: 20 });
    
    let intervalo = setInterval(() => {
        t.set('shadow', t.shadow ? null : brilloNaranja);
        canvas.renderAll();
        parpadeos++;
        if(parpadeos > 5) {
            clearInterval(intervalo);
            t.set('shadow', null);
            canvas.renderAll();
        }
    }, 300);
}

function togglePrecision() {
        modoPrecision = !modoPrecision;
        document.getElementById('lbl_precision').innerText = modoPrecision ? "Modo Precisión: ON" : "Modo Precisión: OFF";
        document.getElementById('fab_menu').classList.remove('active');
        
        Swal.fire({
            title: modoPrecision ? 'Precisión Activada' : 'Precisión Desactivada',
            text: modoPrecision ? 'Al mover objetos, verás un conector y la firma flotará arriba de tu dedo.' : 'Modo de arrastre normal activado.',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function limpiarTodo() {
    Swal.fire({
        title: '¿Limpiar todo el documento?',
        text: "Se eliminarán todas las firmas y aclaraciones.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        confirmButtonText: 'Sí, borrar todo',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            canvas.getObjects().forEach(obj => canvas.remove(obj));
            canvas.discardActiveObject();
            canvas.requestRenderAll();
            Swal.fire({title: '¡Limpio!', text: 'El documento ha sido vaciado.', icon: 'success', timer: 1500, showConfirmButton: false});
        }
    });
    document.getElementById('fab_menu').classList.remove('active');
}

function eliminarSeleccion() { 
    let activos = canvas.getActiveObjects();
    if(activos.length === 0) {
        Swal.fire({title: 'Atención', text: 'Tocá una firma o aclaración primero para seleccionarla y luego presioná Borrar.', icon: 'info', timer: 3000, showConfirmButton: false});
    } else {
        activos.forEach(o => canvas.remove(o)); 
        canvas.discardActiveObject(); 
        canvas.requestRenderAll();
    }
    document.getElementById('fab_menu').classList.remove('active');
}

function vistaPrevia() {
    canvas.discardActiveObject().renderAll();
    let esCelular = window.innerWidth < 768;
    let dataURL = canvas.toDataURL({ format: 'png', multiplier: esCelular ? 1.5 : 2 }); 
    
    Swal.fire({
        title: 'Vista Previa',
        html: `<div style="overflow: auto; max-height: 70vh; border: 1px solid #ddd; background: #e2e8f0;">
                <img src="${dataURL}" style="width: 100%; display: block;">
               </div>`,
        width: '95%',
        confirmButtonText: 'Volver al Editor',
        confirmButtonColor: 'var(--secondary)'
    });
    document.getElementById('fab_menu').classList.remove('active');
}

document.getElementById('archivo_upload').addEventListener('change', e => {
    let file = e.target.files[0]; if(!file) return;
    document.getElementById('controles_formulario').classList.remove('hidden');
    document.getElementById('fab_menu').classList.remove('hidden');
    
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    Swal.fire({ title: 'Procesando Calidad Máxima...', text: 'Esto puede tardar 5 segundos', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    esPdfOriginal = (file.type === 'application/pdf');

    let reader = new FileReader();
    reader.onload = async f => {
        await document.fonts.ready; // Esperar a las fuentes TTF

        if(esPdfOriginal) {
            originalPdfBytes = new Uint8Array(f.target.result);
            pdfjsLib.getDocument({data: originalPdfBytes, disableFontFace: false}).promise.then(pdf => pdf.getPage(1).then(page => {
                // ESCALA 5.0 para nitidez absoluta del QR y letras
                let vp = page.getViewport({ scale: 5.0 });
                let tc = document.createElement('canvas');
                let ctx = tc.getContext('2d');
                
                tc.height = vp.height; tc.width = vp.width;
                
                // TRUCO: Desactivar suavizado para que el QR no se borronee
                ctx.imageSmoothingEnabled = false;
                ctx.webkitImageSmoothingEnabled = false;

                page.render({ 
                    canvasContext: ctx, 
                    viewport: vp,
                    intent: 'print' // FUERZA EL ESPACIADO CORRECTO DE LETRAS
                }).promise.then(() => {
                    let dataHighRes = tc.toDataURL('image/png');
                    
                    // Imagen liviana para la IA (escala 1.0) para que no se clave
                    let vpIA = page.getViewport({ scale: 1.0 });
                    let tcIA = document.createElement('canvas');
                    tcIA.height = vpIA.height; tcIA.width = vpIA.width;
                    page.render({ canvasContext: tcIA.getContext('2d'), viewport: vpIA }).promise.then(() => {
                        ejecutarIA(tcIA.toDataURL('image/png'));
                    });

                    fabric.Image.fromURL(dataHighRes, img => {
                        let r = (document.getElementById('canvas_container').clientWidth - 20) / img.width;
                        canvas.setWidth(img.width * r); canvas.setHeight(img.height * r);
                        canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: r, scaleY: r });
                    });
                });
            })).catch(err => { Swal.close(); Swal.fire('Error', 'No se pudo leer el PDF', 'error'); });
        } else {
            // Lógica para imágenes normales
            originalImageSrc = f.target.result;
            fabric.Image.fromURL(originalImageSrc, img => {
                let r = (document.getElementById('canvas_container').clientWidth - 20) / img.width;
                canvas.setWidth(img.width * r); canvas.setHeight(img.height * r);
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: r, scaleY: r });
            });
            ejecutarIA(originalImageSrc);
        }
    };
    if(esPdfOriginal) reader.readAsArrayBuffer(file); else reader.readAsDataURL(file);
});

function ejecutarIA(imgSrc) {
    Tesseract.recognize(imgSrc, 'spa', { 
        logger: m => console.log(m) 
    }).then(({ data: { text } }) => {
        const lineas = text.split('\n');
        let montoEncontrado = "";
        let fechaEncontrada = "";
        
        lineas.forEach(l => {
            if(l.includes('$')) {
                let m = l.match(/\d+[\.,]\d+/);
                if(m) montoEncontrado = m[0];
            }
            if(l.match(/\d{2}\/\d{2}\/\d{4}/)) {
                let f = l.match(/\d{2}\/\d{2}\/\d{4}/);
                if(f) fechaEncontrada = f[0];
            }
        });

        if(montoEncontrado) document.getElementById('monto_total').value = montoEncontrado.replace(',','.');
        if(fechaEncontrada) document.getElementById('fecha_f').value = fechaEncontrada.split('/').reverse().join('-');

        Swal.close();
        document.getElementById('pantalla_eleccion').classList.add('hidden');
        document.getElementById('pantalla_editor').classList.remove('hidden');
    }).catch(err => {
        console.error(err);
        Swal.close();
        document.getElementById('pantalla_eleccion').classList.add('hidden');
        document.getElementById('pantalla_editor').classList.remove('hidden');
    });
}

async function guardarYProcesar() {
    let fd = new FormData(), tipo = document.getElementById('titulo_editor').innerText;
    let t_id = document.getElementById('terapeuta_id').value;
    if (!t_id) { Swal.fire('Error', 'Seleccioná un terapeuta.', 'error'); return; }

    fd.append('terapeuta_id', t_id);
    fd.append('tipo_documento', tipo);
    fd.append('mes', document.getElementById('mes').value);
    fd.append('anio', document.getElementById('anio').value);
    
    if(tipo === 'Factura') {
        fd.append('monto', document.getElementById('monto_total').value);
        fd.append('beneficiario', document.getElementById('paciente_f').value);
        fd.append('prestacion', document.getElementById('prestacion_f').value);
        fd.append('sesiones', document.getElementById('sesiones_f').value);
        fd.append('fecha', document.getElementById('fecha_f').value);
    } else {
        fd.append('beneficiario', document.getElementById('paciente_p').value);
        fd.append('prestacion', document.getElementById('prestacion_p').value);
    }

    canvas.discardActiveObject().renderAll();
    Swal.fire({ title: 'Procesando PDF Original...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        if (esPdfOriginal && originalPdfBytes) {
            // USAMOS PDF-LIB PARA INYECTAR EN EL ORIGINAL
            const pdfDoc = await PDFLib.PDFDocument.load(originalPdfBytes);
            const pages = pdfDoc.getPages();
            const firstPage = pages[0];
            const { width, height } = firstPage.getSize();

            // Calculamos la escala entre el canvas visual y el PDF real
            const scaleX = width / canvas.getWidth();
            const scaleY = height / canvas.getHeight();

            const objetos = canvas.getObjects();
            for (const obj of objetos) {
                if (obj.type === 'image') {
                    const imgUrl = obj.toDataURL();
                    const imgBytes = await fetch(imgUrl).then(res => res.arrayBuffer());
                    const embeddedImg = await pdfDoc.embedPng(imgBytes);
                    
                    firstPage.drawImage(embeddedImg, {
                        x: obj.left * scaleX - (obj.width * obj.scaleX * scaleX / 2),
                        y: height - (obj.top * scaleY) - (obj.height * obj.scaleY * scaleY / 2),
                        width: obj.width * obj.scaleX * scaleX,
                        height: obj.height * obj.scaleY * scaleY,
                    });
                } else if (obj.type === 'text' || obj.type === 'i-text') {
                    firstPage.drawText(obj.text, {
                        x: obj.left * scaleX - (obj.width * scaleX / 2),
                        y: height - (obj.top * scaleY) - (obj.height * scaleY / 2),
                        size: obj.fontSize * scaleX,
                        color: PDFLib.rgb(0, 0, 0),
                    });
                }
            }
            const pdfBytes = await pdfDoc.save();
            fd.append('pdf_final', new Blob([pdfBytes], { type: 'application/pdf' }), 'documento_firmado.pdf');
        } else {
            // Si es una imagen, seguimos usando el multiplier HD
            let mult = canvas.backgroundImage ? (canvas.backgroundImage.width / canvas.getWidth()) : 2;
            fd.append('imagen_hd', canvas.toDataURL({ format: 'png', multiplier: mult }));
        }

        const res = await fetch('ajax_guardar_documento.php', { method: 'POST', body: fd });
        const d = await res.json();
        if(d.status === 'success') {
            window.location.href = 'index.php';
        } else {
            Swal.fire('Error', d.message, 'error');
        }
    } catch (err) {
        // PLAN B: Si el PDF tiene protección de ARCA, sacamos una "foto" HD del documento firmado
        console.warn("PDF protegido detectado. Usando captura de alta fidelidad.");
        
        let mult = canvas.backgroundImage ? (canvas.backgroundImage.width / canvas.getWidth()) : 2;
        fd.append('imagen_hd', canvas.toDataURL({ format: 'png', multiplier: mult }));
        
        try {
            const res = await fetch('ajax_guardar_documento.php', { method: 'POST', body: fd });
            const d = await res.json();
            if(d.status === 'success') {
                window.location.href = 'index.php';
            } else {
                Swal.fire('Error', d.message, 'error');
            }
        } catch (postErr) {
            Swal.fire('Error', 'Problema de conexión al guardar el documento.', 'error');
        }
    }
}
</script>
<?php include 'includes/footer.php'; ?>