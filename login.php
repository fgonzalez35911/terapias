<?php
session_start();
if(isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso - SGR</title>
    <link rel="manifest" href="/terapias/manifest.json">
    <meta name="theme-color" content="#2c3e50">
    <link rel="stylesheet" href="/terapias/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-login">
    <div class="login-container card">
        <h2 class="text-center">Ingreso SGR</h2>
        <form id="loginForm">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" id="usuario" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-full">Ingresar</button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            let datos = new FormData();
            datos.append('usuario', document.getElementById('usuario').value);
            datos.append('password', document.getElementById('password').value);

            fetch('auth.php', {
                method: 'POST',
                body: datos
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    window.location.href = 'index.php';
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'Intentar de nuevo'
                    });
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Hubo un problema de conexión.', 'error');
            });
        });
    </script>
</body>
</html>
