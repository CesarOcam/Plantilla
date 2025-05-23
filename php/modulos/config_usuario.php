<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Configuraciones de Usuario</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">

        <link rel="stylesheet" href="/portal_web/Contabilidad/css/style.css">
        <link rel="stylesheet" href="/portal_web/Contabilidad/css/style2.css">
</head>

<body>
    <?php
    include($_SERVER['DOCUMENT_ROOT'] . '/portal_web/Contabilidad/php/vistas/navbar.php');
    ?>
    <div class="container mt-5">
        <h3 class="mb-4"><i class="bi bi-gear me-2"></i>Configuraciones de Usuario</h3>

        <!-- Sección de perfil -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Información del Usuario</h5>
                <p><strong>Nombre:</strong> <?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></p>
                <!-- Puedes agregar más datos del usuario aquí -->
            </div>
        </div>

        <!-- Sección para cambiar contraseña -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Cambiar Contraseña</h5>
                <form>
                    <div class="mb-3">
                        <label for="clave_actual" class="form-label">Contraseña actual</label>
                        <input type="password" class="form-control" id="clave_actual" required>
                    </div>
                    <div class="mb-3">
                        <label for="nueva_clave" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="nueva_clave" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmar_clave" class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" id="confirmar_clave" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </form>
            </div>
        </div>

        <!-- Sección adicional (tema, notificaciones, etc.) -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Preferencias</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="modoOscuro">
                    <label class="form-check-label" for="modoOscuro">Modo oscuro</label>
                </div>
            </div>
        </div>
    </div>

    <!-- JS de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>