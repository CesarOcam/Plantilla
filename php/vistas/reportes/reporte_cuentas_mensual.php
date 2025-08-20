<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
require_once('../../modulos/conexion.php');

?>

<!DOCTYPE html>
<html lang="en">

<body class="cat-clientes">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Estado de Cuentas</title>
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
    </head>

    <?php
    include_once __DIR__ . '/../../../config.php';

    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>

    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <form id="form_factura_pp" method="POST" action="reporte_cuentas_h1.php">
                <div class="card-header">
                    <div class="container-fluid mt-4">
                        <div class="row d-flex justify-content-center align-items-end gap-2">
                            <div class="col-md-4 col-sm-6 mb-2">
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
                                        echo "<option value=\"$valor\" id=\"option-$valor\">$texto</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row flex-nowrap">
                            <!-- Barra lateral -->
                            <div class="col-12 col-sm-2 col-md-1 border-end">
                                <div class="nav flex-column nav-pills" id="v-meses-tab" role="tablist"
                                    aria-orientation="vertical">
                                    <?php
                                    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                                    foreach ($meses as $i => $mes): ?>
                                        <button class="nav-link text-start <?php echo $i === 0 ? 'active' : ''; ?>"
                                            id="v-tab-<?php echo $i; ?>" data-bs-toggle="pill"
                                            data-bs-target="#v-pane-<?php echo $i; ?>" type="button" role="tab"
                                            aria-controls="v-pane-<?php echo $i; ?>"
                                            aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                                            <?php echo ucfirst($mes); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Contenido del mes seleccionado -->
                            <div class="col-md-10 col-sm-8" style="height: 700px;">
                                <div class="tab-content p-3 border rounded bg-white shadow-sm"
                                    style="height: 100%; overflow-y: auto;">
                                    <?php foreach ($meses as $i => $mes): ?>
                                        <div class="tab-pane fade <?php echo $i === 0 ? 'show active' : ''; ?>"
                                            id="v-pane-<?php echo $i; ?>" role="tabpanel"
                                            aria-labelledby="v-tab-<?php echo $i; ?>">
                                            <div class="row d-flex justify-content-end">
                                                <div class="col-md-2 col-sm-6 mb-2 d-flex justify-content-end">
                                                    <button type="submit"
                                                        class="btn btn-outline-success rounded-0 d-flex justify-content-center align-items-center gap-2"
                                                        id="btn_buscar">
                                                        <i class="bi bi-download"></i>Descargar
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="tabla-scroll"
                                                style="max-height: 800px; overflow-y: auto; overflow-x: auto; border: 1px solid #ddd; padding: 5px;">
                                                <!-- Aquí se cargará la tabla -->
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
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

        });
    </script>
    <script src="../../../js/reportes/reporte_cuentas_mes.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>

</body>

</html>