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

    <div class="editor-main card" id="canvas_container">
        <canvas id="editor_canvas"></canvas>
    </div>
</div>

<div id="fab_menu" class="fab-container hidden">
    <button class="fab-main" onclick="this.parentElement.classList.toggle('active')">➕</button>
    <div class="fab-menu">
        <button class="fab-item" onclick="eliminarSeleccion()"><span class="fab-label">Borrar</span><div class="fab-icon">🗑️</div></button>
        <button class="fab-item" onclick="agregarAclaracion()"><span class="fab-label">Aclaración</span><div class="fab-icon">🖋️</div></button>
        <button id="btn_firma_dinamico" class="fab-item" onclick="accionFirma()"><span id="lbl_firma" class="fab-label">Firmar</span><div class="fab-icon">📝</div></button>
    </div>
</div>

<input type="hidden" id="user_nombre" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>">
<input type="hidden" id="user_dni" value="<?php echo htmlspecialchars($user['dni']); ?>">

<script>
let canvas, modoMultiFirma = false, firmaGuardada = '<?php echo $user['firma_digital']; ?>';
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
    
    canvas = new fabric.Canvas('editor_canvas', { allowTouchScrolling: true });
    
    // CORRECCIÓN MULTI-FIRMA: Evento corregido
    canvas.on('mouse:down', function(opt) {
        if(modoMultiFirma && firmaGuardada && !opt.target) {
            fabric.Image.fromURL(firmaGuardada, img => {
                img.scaleToWidth(150);
                img.set({ left: opt.pointer.x, top: opt.pointer.y, originX: 'center', originY: 'center', cornerSize: 24 });
                canvas.add(img); canvas.setActiveObject(img);
            });
        }
    });
}

function accionFirma() {
    if(document.getElementById('titulo_editor').innerText === 'Factura') {
        let vpt = canvas.viewportTransform;
        let cx = (canvas.width / 2 - vpt[4]) / vpt[0];
        let cy = (canvas.height / 2 - vpt[5]) / vpt[3];
        fabric.Image.fromURL(firmaGuardada, img => {
            img.scaleToWidth(150); img.set({ left: cx, top: cy, originX: 'center', originY: 'center', cornerSize: 24 });
            canvas.add(img); canvas.setActiveObject(img);
        });
    } else {
        modoMultiFirma = !modoMultiFirma;
        document.getElementById('lbl_firma').innerText = modoMultiFirma ? 'Desactivar Multi' : 'Activar Multi-Firma';
        Swal.fire('Multi-Firma', modoMultiFirma ? 'Activada: Tocá el PDF' : 'Desactivada', 'info');
    }
    document.getElementById('fab_menu').classList.remove('active');
}

function agregarAclaracion() {
    let vpt = canvas.viewportTransform;
    let cx = (canvas.width / 2 - vpt[4]) / vpt[0];
    let cy = (canvas.height / 2 - vpt[5]) / vpt[3];
    let t = new fabric.Text(`${document.getElementById('user_nombre').value}\nDNI: ${document.getElementById('user_dni').value}`, {
        left: cx, top: cy, originX: 'center', originY: 'center', fontSize: 22, textAlign: 'center', fontWeight: 'bold'
    });
    canvas.add(t); canvas.setActiveObject(t);
    document.getElementById('fab_menu').classList.remove('active');
}

function eliminarSeleccion() { 
    canvas.getActiveObjects().forEach(o => canvas.remove(o)); 
    canvas.discardActiveObject(); 
    document.getElementById('fab_menu').classList.remove('active');
}

document.getElementById('archivo_upload').addEventListener('change', e => {
    let file = e.target.files[0]; if(!file) return;
    document.getElementById('controles_formulario').classList.remove('hidden');
    document.getElementById('fab_menu').classList.remove('hidden');
    Swal.fire({ title: 'IA Analizando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    let reader = new FileReader();
    reader.onload = f => {
        if(file.type === 'application/pdf') {
            pdfjsLib.getDocument(new Uint8Array(f.target.result)).promise.then(pdf => pdf.getPage(1).then(page => {
                let vp = page.getViewport({ scale: 3.5 }), tc = document.createElement('canvas');
                tc.height = vp.height; tc.width = vp.width;
                page.render({ canvasContext: tc.getContext('2d'), viewport: vp }).promise.then(() => {
                    let data = tc.toDataURL('image/png'); 
                    fabric.Image.fromURL(data, img => {
                        let r = (document.getElementById('canvas_container').clientWidth - 20) / img.width;
                        canvas.setWidth(img.width * r); canvas.setHeight(img.height * r);
                        canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: r, scaleY: r });
                    });
                    ejecutarIA(data);
                });
            }));
        } else {
            fabric.Image.fromURL(f.target.result, img => {
                let r = (document.getElementById('canvas_container').clientWidth - 20) / img.width;
                canvas.setWidth(img.width * r); canvas.setHeight(img.height * r);
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), { scaleX: r, scaleY: r });
            });
            ejecutarIA(f.target.result);
        }
    };
    if(file.type === 'application/pdf') reader.readAsArrayBuffer(file); else reader.readAsDataURL(file);
});

function ejecutarIA(data) {
    Tesseract.recognize(data, 'spa').then(({ data: { text } }) => {
        let t = text.replace(/\n/g, ' '), tl = t.toLowerCase();
        terapeutasDB.forEach(th => { if(tl.includes(th.nombre.toLowerCase().split(' ')[0])) document.getElementById('terapeuta_id').value = th.id; });
        let mBen = t.match(/(?:Paciente|Beneficiario|Nombre)[\s:]*([A-Za-z\s]+)(?:DNI|Periodo|Obra|54)/i);
        let mPre = t.match(/(?:Prestaci[oó]n|Servicio)[\s:]*([A-Za-z\sÁÉÍÓÚáéíóú]+)/i);
        
        if(document.getElementById('titulo_editor').innerText === 'Factura') {
            if(mBen) document.getElementById('paciente_f').value = mBen[1].trim();
            if(mPre) document.getElementById('prestacion_f').value = mPre[1].split('8,00')[0].trim();
            let mTotal = t.match(/Importe Total[\s:\$]*([\d\.,]+)/i);
            if(mTotal) document.getElementById('monto_total').value = parseFloat(mTotal[1].replace(/\./g, '').replace(',', '.'));
            let mFecha = t.match(/Emisi[oó]n[\s:]*(\d{2}\/\d{2}\/\d{4})/i);
            if(mFecha) document.getElementById('fecha_f').value = mFecha[1];
            let mCant = t.match(/(\d+)[,\.]\d{2}\s*unidades/i);
            if(mCant) document.getElementById('sesiones_f').value = mCant[1];
        } else {
            if(mBen) document.getElementById('paciente_p').value = mBen[1].trim();
            if(mPre) document.getElementById('prestacion_p').value = mPre[1].trim();
        }
        Swal.fire('IA Lista', 'Revisá los datos', 'success');
    });
}

function guardarYProcesar() {
    let fd = new FormData(), tipo = document.getElementById('titulo_editor').innerText;
    fd.append('terapeuta_id', document.getElementById('terapeuta_id').value);
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
    fd.append('imagen_hd', canvas.toDataURL({ format: 'png', multiplier: 2 }));
    fetch('ajax_guardar_documento.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if(d.status === 'success') window.location.href = 'index.php'; });
}
</script>
<?php include 'includes/footer.php'; ?>