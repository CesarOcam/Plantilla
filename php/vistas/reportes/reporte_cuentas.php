<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
require_once('../../modulos/conexion.php');

$stmt = $con->prepare("
    SELECT Id, Numero, Nombre
    FROM cuentas
    WHERE Activo = 1
    AND EmpresaId = 2
    AND (
        (SUBSTRING_INDEX(Numero, '-', 1) = '113' AND Numero LIKE '113-%')
    )
    ORDER BY Numero ASC
");

$stmt->execute();
$subcuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $con->prepare("
    SELECT Id, Nombre 
    FROM beneficiarios
    ORDER BY Nombre ASC
");
$stmt->execute();
$beneficiario = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">
        <!-- Fechas -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- Bootstrap Icons CDN para los íconos -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <!-- SELECT 2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <link rel="stylesheet" href="../../../css/style.css">
        <link rel="stylesheet" href="../../../css/style2.css">
    </head>

    <?php
    include_once __DIR__ . '/../../../config.php';

    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>

    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <form id="form_factura_pp" method="POST" action="reporte_cuentas_h1.php">
                <div class="card-header">
                    <div class="d-flex flex-column mb-3">
                        <div class="row m-3 mb-0 align-items-end">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label for="subcuentaInput" class="form-label small mb-1">SUBCUENTA</label>
                                <select id="subcuentaInput" name="cuentaId" class="form-select select2"
                                    style="width: 100%;" required>
                                    <option value="">Seleccione una subcuenta</option>
                                    <?php
                                    $stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas WHERE CuentaPadreId IS NOT NULL ORDER BY Numero ASC");
                                    $stmt->execute();
                                    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($cuentas as $cuenta) {
                                        $valor = $cuenta['Id'];
                                        $texto = $cuenta['Numero'] . ' - ' . $cuenta['Nombre'];
                                        echo "<option value=\"$valor\">$texto</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2 position-relative">
                                <label for="fechaDesdeInput" class="form-label small mb-1">Fecha Desde:</label>
                                <i class="bi bi-calendar-week position-absolute"
                                    style="left: 10px; top: 36px; z-index: 10; color: gray;"></i>
                                <input id="fechaDesdeInput" name="fechaDesdeInput" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Fecha Desde" required>
                            </div>

                            <div class="col-md-2 col-sm-6 mb-2 position-relative">
                                <label for="fechaHastaInput" class="form-label small mb-1">Fecha Hasta:</label>
                                <i class="bi bi-calendar-week position-absolute"
                                    style="left: 10px; top: 36px; z-index: 10; color: gray;"></i>
                                <input id="fechaHastaInput" name="fechaHastaInput" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Fecha Hasta" required>
                            </div>

                            <div class="col-md-4 col-sm-6 mb-2 d-flex gap-2">
                                <button type="submit" class="btn btn-secondary rounded-0 w-100" id="btn_buscar"
                                    disabled>Consultar</button>
                                <button type="button" class="btn btn-outline-secondary rounded-0 w-100"
                                    id="btn_limpiar">Limpiar</button>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-5" style="border-top: 2px solid #000;">
                    <div id="tabla-pp-container">
                        <?php //include('../../modulos/consultas/tabla_facturas_cuentas.php'); ?>
                    </div>

                </div>
            </form>
            <div class="card-body">

            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#subcuentaInput').select2({
                placeholder: "Seleccione una subcuenta",
                allowClear: false
            });

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    const input = document.querySelector('.select2-container--open .select2-search__field');
                    if (input) input.focus();
                }, 100);
            });

            const btn = document.getElementById("btn_buscar");
            const fechaDesde = document.getElementById("fechaDesdeInput");
            const fechaHasta = document.getElementById("fechaHastaInput");
            const subcuenta = document.getElementById("subcuentaInput");

            function validarCampos() {
                console.log("Desde:", fechaDesde.value);
                console.log("Hasta:", fechaHasta.value);
                console.log("Subcuenta:", subcuenta.value);

                if (fechaDesde.value.trim() !== "" &&
                    fechaHasta.value.trim() !== "" &&
                    subcuenta.value !== "") {
                    btn.disabled = false;
                } else {
                    btn.disabled = true;
                }
            }

            flatpickr("#fechaDesdeInput", {
                dateFormat: "Y-m-d",
                onChange: validarCampos
            });

            flatpickr("#fechaHastaInput", {
                dateFormat: "Y-m-d",
                onChange: validarCampos
            });

            fechaDesde.addEventListener("input", validarCampos);
            fechaHasta.addEventListener("input", validarCampos);
            subcuenta.addEventListener("change", validarCampos);

            validarCampos(); // Ejecutar al inicio por si ya hay valores
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>

</body>

</html>