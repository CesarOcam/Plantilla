<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<body class="cat-aduanas">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Usuarios</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">

        <link rel="stylesheet" href="../../../css/style.css">
    </head>

    <?php
    include_once __DIR__ . '/../../../config.php';

    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>

    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <div class="card-header">
                <div class="d-flex flex-column mb-3">
                    <div class="row w-100">
                        <h5 class="mt-4 mb-1">
                            <i class="fas fa-user me-2 text-secondary"></i> Usuarios
                        </h5>
                        <div class="col-12 col-sm-10 d-flex align-items-center">
                            <input id="filtroInput" type="text"
                                class="form-control w-100 rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" placeholder="Filtrar aduana por nombre"
                                aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                        </div>
                        
                    </div>
                </div>

                <div id="tabla-aduanas-container">
                    <?php include('../../modulos/consultas_cat/tabla_usuarios.php'); ?>
                </div>
            </div>

            <div class="card-body">

            </div>
        </div>
    </div>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
        crossorigin="anonymous"></script>
    <script>
        // Obtener el interruptor
        const modeToggle = document.getElementById('modeToggle');

        // Cambiar el modo cuando se haga clic en el interruptor
        modeToggle.addEventListener('change', function () {
            if (modeToggle.checked) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        });
        // Inicializar tooltips de Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        //Filtrado de la tabla-aduanas-container
        document.getElementById("filtroInput").addEventListener("input", function () {
            const filtro = this.value;

            // Hacer petición AJAX al archivo que genera la tabla
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "../../modulos/consultas_cat/tabla_aduanas.php?filtro=" + encodeURIComponent(filtro), true);
            xhr.onload = function () {
                if (this.status === 200) {
                    document.getElementById("tabla-aduanas-container").innerHTML = this.responseText;
                }
            };
            xhr.send();
        });
    </script>
    <script src="../../../js/desactivar_Usuarios.js"></script>
</body>

</html>