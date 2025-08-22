<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}

include_once('../../modulos/conexion.php'); // Ajusta el path según sea necesario

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 después -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Iconos Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Fechas -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">
</head>

<?php
include_once __DIR__ . '/../../../config.php';

include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Pago" method="POST">
            <div class="card-header formulario_clientes">
                <div class="row align-items-center justify-content-between mb-3">
                    <!-- Título -->
                    <div class="col-auto">
                        <h5 class="mb-0">Realizar Pago</h5>
                    </div>

                    <!-- Botón con estilo moderno y tooltip -->
                    <div class="col-auto">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#modalSolicitudes"
                            class="btn btn-outline-secondary d-flex align-items-center px-3 py-2 rounded-0 shadow-sm"
                            style="font-size: 0.9rem;" title="Ver Solicitudes">
                            <i class="fas fa-file-alt me-2"></i> Ver Solicitudes
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-3 col-sm-5 d-flex flex-column mt-4">
                        <label for="BeneficiarioId" class="form-label text-muted small">BENEFICIARIO:</label>
                        <input id="BeneficiarioId" name="BeneficiarioId" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="" readonly>
                    </div>
                    <div class="col-2 col-sm-1 d-flex flex-column mt-4">
                        <label for="NoSolicitud" class="form-label text-muted small">NO. SOLICITUD:</label>
                        <input id="NoSolicitud" name="NoSolicitud" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="" readonly>
                    </div>
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="FechaAlta" class="form-label text-muted small">FECHA:</label>
                        <input id="FechaAlta" name="FechaAlta" type="text"
                            class="form-control ps-4 rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="">
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="Fecha" class="form-label text-muted small">FECHA PÓLIZA:</label>
                        <input id="Fecha" name="Fecha" type="text"
                            class="form-control ps-4 rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="">
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="AduanaId" class="form-label text-muted small">ADUANA:</label>
                        <input id="AduanaId" name="AduanaId" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="" readonly>
                        <input id="ReferenciaFacturaId" name="ReferenciaFacturaId" type="hidden" value="">
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-12">
                        <table class="table table-bordered table-sm tabla-partidas-pagar" id="tabla-partidas">
                            <thead class="table-light">
                                <tr>
                                    <th>Subcuenta</th>
                                    <th>Cargo</th>
                                    <th>Abono</th>
                                    <th>Exportador</th>
                                    <th>Observaciones</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="submit" class="btn btn-secondary rounded-0" id="btn_guardar">Guardar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalSolicitudes" tabindex="-1" aria-labelledby="modalSolicitudesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius: 0.3rem;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSolicitudesLabel">Solicitudes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="tabla-aduanas-container">
                    <?php include('../../modulos/consultas_traf/tabla_Solicitudes.php'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar Calendarios
    flatpickr("#FechaAlta", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#Fecha", {
        dateFormat: "Y-m-d"
    });

    $(document).on('select2:open', () => {
        setTimeout(() => {
            const input = document.querySelector('.select2-container--open .select2-search__field');
            if (input) input.focus();
        }, 100);
    });

    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });

</script>

<script src="../../../js/guardar_Pago.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>