<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<body class="cat-clientes">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Polizas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">
        <!-- Fechas -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- Bootstrap Icons CDN para los íconos -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        <link rel="stylesheet" href="../../../css/style.css">
        <link rel="stylesheet" href="../../../css/style2.css">
    </head>

    <?php
    include_once __DIR__ . '/../../../config.php';

    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>

    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <div class="card-header">
                <div class="d-flex flex-column mb-3">
                    <div class="row m-3 mb-0">
                        <div class="col-4 d-flex flex-column">
                            <label for="statusInput" class="form-label small mb-0">Tipo de Reporte *</label>
                            <select id="statusInput" class="form-select rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por status">
                                <option value="">Facturación</option>
                                <option value="1">Gastos</option>
                                <option value="2">Gastos a terceros</option>
                                <option value="3">Por cuenta</option>
                                <option value="4">Por subcuenta</option>
                            </select>
                        </div>

                        <div class="col-10 col-sm-4 d-flex align-items-center mt-4 position-relative">
                            <i class="bi bi-calendar-week"
                                style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                            <input id="fechaDesdeInput" name="fechaDesdeInput" type="text"
                                class="form-control ps-4 rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" placeholder="Fecha Desde">
                        </div>

                        <div class="col-10 col-sm-4 d-flex align-items-center mt-4 position-relative">
                            <i class="bi bi-calendar-week"
                                style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                            <input id="fechaHastaInput" name="fechaHastaInput" type="text"
                                class="form-control ps-4 rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" placeholder="Fecha Hasta">
                        </div>
                    </div>
                </div>

                <div class="row justify-content-end mt-auto">
                    <div class="col-auto d-flex align-items-center mt-3 mb-3">
                        <button type="button" class="btn btn-outline-danger rounded-0" onclick="">Salir</button>
                    </div>
                    <div class="col-auto d-flex align-items-center mt-3 mb-3">
                        <button type="submit" class="btn btn-outline-secondary rounded-0" id="btn_guardar">
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        flatpickr("#fechaDesdeInput", {
            dateFormat: "Y-m-d"
        });
        flatpickr("#fechaHastaInput", {
            dateFormat: "Y-m-d"
        });

        document.getElementById("btn_buscar").addEventListener("click", function () {
            const status = document.getElementById("statusInput").value;
            const fechaDesde = document.getElementById("fechaDesdeInput").value;
            const fechaHasta = document.getElementById("fechaHastaInput").value;
            const poliza = document.getElementById("polizaInput").value;
            const beneficiario = document.getElementById("beneficiarioInput").value;

            const params = new URLSearchParams({
                status,
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                poliza,
                beneficiario
            });

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "../../modulos/consultas/tabla_polizas.php?" + params.toString(), true);
            xhr.onload = function () {
                if (this.status === 200) {
                    document.getElementById("tabla-polizas-container").innerHTML = this.responseText;
                }
            };
            xhr.send();
        });

        // Limpiar filtros
        document.getElementById("btn_limpiar").addEventListener("click", function () {
            document.getElementById("statusInput").value = "";
            document.getElementById("fechaDesdeInput").value = "";
            document.getElementById("fechaHastaInput").value = "";
            document.getElementById("polizaInput").value = "";
            document.getElementById("beneficiarioInput").value = "";

            document.getElementById("btn_buscar").click(); // recargar con filtros vacíos
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>

</body>

</html>