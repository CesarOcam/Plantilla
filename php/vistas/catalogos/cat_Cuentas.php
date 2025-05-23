<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<body class="cat-cuentas">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
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
                            <i class="fas fa-file-invoice-dollar me-2 text-secondary"></i> Catálogo Cuentas Contables
                        </h5>
                        <div class="col-12 col-sm-10 d-flex align-items-center">
                            <input id="filtroInput" type="text"
                                class="form-control w-100 rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" placeholder="Filtrar cuenta por nombre"
                                aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                        </div>
                        <div
                            class="col-12 col-sm-2 d-flex align-items-center justify-content-start justify-content-sm-end mt-2 mt-sm-0">
                            <!-- Botón solo icono sin borde ni fondo, más a la izquierda -->
                            <button id="btnDesactivar" type="button" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="Desactivar"
                                style="border: none; background: transparent; padding: 0; font-size: 1.5rem; color:rgba(161, 155, 155, 0.62); cursor: pointer; display: none;"
                                class="me-5 mt-2">
                                <i class="fas fa-ban"></i>
                            </button>
                            <a href="../form_cuentas.php"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Agregar nuevo"
                                style="text-decoration: none; color: black; font-size: 1.3rem;">
                                <i class="fas fa-plus mt-2"></i>
                            </a>
                            <span class="mx-2">
                                <h5>|</h5>
                            </span>

                            <!-- Interruptor de modo oscuro / claro -->
                            <label class="switch mt-2">
                                <input type="checkbox" id="modeToggle">
                                <span class="slider"></span>
                            </label>

                            <p class="mb-0 ms-2 mt-2">Mostrar inactivos</p>
                        </div>
                    </div>
                </div>

                <div id="tabla-cuentas-container">
                    <?php include('../../modulos/consultas_cat/tabla_cuentas.php'); ?>
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

        //Filtrado de la tabla-cuentas-container
        document.getElementById("filtroInput").addEventListener("input", function () {
            const filtro = this.value;

            // Hacer petición AJAX al archivo que genera la tabla
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "../../modulos/consultas_cat/tabla_cuentas.php?filtro=" + encodeURIComponent(filtro), true);
            xhr.onload = function () {
                if (this.status === 200) {
                    document.getElementById("tabla-cuentas-container").innerHTML = this.responseText;
                }
            };
            xhr.send();
        });
    </script>
    <script src="../../../js/desactivar_Cuentas.js"></script>

</body>

</html>