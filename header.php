<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGR - Reintegros IOSFA</title>
    <link rel="manifest" href="/terapias/manifest.json">
    <meta name="theme-color" content="#2c3e50">
    <link rel="stylesheet" href="/terapias/assets/css/style.css?v=6">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</head>
<body>
    <?php if(strpos($_SERVER['SCRIPT_NAME'], 'login.php') === false): ?>
    <nav class="navbar">
        <div class="logo"><h2>SGR</h2></div>
        
        <button class="menu-toggle" id="menuToggle">☰</button>

        <div class="nav-links" id="navLinks">
            <a href="index.php">Inicio</a>
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] !== 'terapeuta'): ?>
                <a href="subir_documentos.php">Subir Documentos</a>
                <a href="terapeutas.php">Terapeutas</a>
                <a href="configuracion.php">Ajustes</a>
            <?php endif; ?>
            <a href="perfil.php">Mi Perfil</a>
            <a href="logout.php" class="btn btn-danger ml-1">Salir</a>
        </div>
    </nav>
    <script>
        // SCRIPT PARA QUE EL MENÚ BAJE EN CELULARES
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
        });
    </script>
    <div class="container">
    <?php endif; ?>
