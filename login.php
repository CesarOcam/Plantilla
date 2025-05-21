<?php
session_start();
include('php/modulos/conexion.php');

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/login.css" rel="stylesheet" />
</head>

<body>
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="card card-login d-flex flex-row w-100 shadow" style="max-width: 900px; border: none;">
            <div class="col-md-6 d-flex justify-content-center align-items-center p-4 bg-light">
                <img src="img/logo2.png" alt="Logo" class="img-fluid" style="max-width: 200px; height: auto;">
            </div>
            <div class="col-md-6 p-5 d-flex flex-column justify-content-center align-items-center bg-white rounded-end">
                <h3 class="mb-4 text-center">Iniciar Sesión</h3>
                <form id="loginForm" class="w-100" novalidate>
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required />
                    </div>
                    <div class="mb-3">
                        <label for="clave" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="clave" name="clave" required />
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    <div id="errorMsg" class="text-danger mt-3 text-center" style="display:none;">
                        Usuario o contraseña incorrectos.
                    </div>
                    <p class="text-muted mt-3 mb-0 text-center" style="font-size: 0.9rem;">¿Olvidaste tu contraseña?</p>
                </form>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const usuarioInput = document.getElementById('usuario');
            const claveInput = document.getElementById('clave');
            const errorMsg = document.getElementById('errorMsg');

            // Limpiar estados
            errorMsg.style.display = 'none';
            usuarioInput.classList.remove('is-invalid');
            claveInput.classList.remove('is-invalid');

            // Obtener datos
            const data = {
                usuario: usuarioInput.value.trim(),
                clave: claveInput.value.trim()
            };

            const response = await fetch('php/modulos/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const text = await response.text();
            console.log('Respuesta cruda:', text);

            try {
                const result = JSON.parse(text);

                if (result.success) {
                    window.location.href = 'php/vistas/inicio.php';
                } else {
                    errorMsg.style.display = 'block';
                    usuarioInput.classList.add('is-invalid');
                    claveInput.classList.add('is-invalid');
                }
            } catch (e) {
                console.error('No se pudo parsear JSON:', e);
                alert('Ocurrió un error, intenta de nuevo más tarde.');
            }

        });
    </script>

</body>

</html>