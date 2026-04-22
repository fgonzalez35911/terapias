<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Como este archivo está en /includes, subimos un nivel para encontrar db.php
require_once __DIR__ . '/../db.php';

function tienePermiso($permiso_buscado) {
    global $pdo;
    $rol_actual = $_SESSION['rol'] ?? 'user';
    
    try {
        $stmt = $pdo->prepare("SELECT permisos_json FROM roles_dinamicos WHERE nombre_rol = ?");
        $stmt->execute([$rol_actual]);
        $resultado = $stmt->fetchColumn();
        
        if ($resultado) {
            $permisos_array = json_decode($resultado, true);
            // Verifica si el permiso existe en el JSON y si está en true (1)
            if (isset($permisos_array[$permiso_buscado]) && $permisos_array[$permiso_buscado] == true) {
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function verificarAcceso($permiso) {
    if (!tienePermiso($permiso)) {
        header("Location: index.php?error=sin_permiso");
        exit;
    }
}
?>