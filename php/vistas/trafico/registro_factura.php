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
    <title>Pólizas</title>
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
        <form id="form_Factura" method="POST">
            <div class="card-header formulario_clientes">
                <div class="row align-items-center justify-content-between mb-3">
                    <!-- Título -->
                    <div class="col-auto">
                        <h5 class="mb-3">Registrar Facturas</h5>
                        <p class="mb-0">*Sólo se tomarán en cuenta facturas que tengan asignadas una referencia</p>
                    </div>
                </div>
                <div class="row">
                    <!-- Columna izquierda: Subir archivos -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content rounded-0">
                                <div class="modal-body">
                                    <div class="col-md-12">
                                        <div class="card shadow-sm h-100 rounded-0">
                                            <div
                                                class="card-body d-flex flex-column justify-content-center text-center">
                                                <h5 class="text-secondary mb-3">Subir Facturas</h5>
                                                <div id="uploadBox"
                                                    class="border p-5 bg-light d-flex flex-column align-items-center justify-content-center"
                                                    style="border-style: dashed; min-height: 150px;">
                                                    <div id="uploadPrompt" class="text-center">
                                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-3"></i>
                                                        <p class="text-muted small mb-2">Arrastra los archivos aquí o
                                                            haz clic en el botón para subir</p>
                                                        <button type="button"
                                                            class="btn btn-outline-secondary btn-md mt-2 rounded-0"
                                                            onclick="document.getElementById('fileUpload').click();">
                                                            Seleccionar Archivos
                                                        </button>
                                                    </div>

                                                    <div id="uploadAlert" class="rounded-0"
                                                        style="margin-top: 1rem; display: none;"></div>

                                                    <input type="file" class="form-control-file d-none" id="fileUpload"
                                                        multiple>
                                                    <div id="fileRows" class="mt-4 mb-4 w-100 text-start"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" id="btnVaciarArchivos" class="btn btn-danger rounded-0"
                                        data-bs-dismiss="modal">
                                        <i class="fas fa-trash-alt"></i> Cancelar
                                    </button>
                                    <button type="button" id="btnCargarTodos" class="btn btn-secondary rounded-0"
                                        data-bs-dismiss="modal">
                                        <i class="bi bi-upload"></i> Cargar al servidor
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cold col-md-12">
                        <div id="tabla-aduanas-container">
                            <?php include_once("../../modulos/consultas_traf/tabla_facturas.php"); ?>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-between mt-5">
                    <div class="col-auto mt-3 mb-5">
                        <button type="button" class="btn btn-outline-secondary  rounded-0" data-bs-toggle="modal"
                            data-bs-target="#exampleModal">
                            Subir Facturas
                        </button>
                    </div>
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="submit" class="btn btn-secondary rounded-0" id="btn_guardar">Crear
                            Solicitudes</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
<script src="../../../js/archivos/xml_Factura.js"></script>

<script>
    $(document).on('select2:open', () => {
        setTimeout(() => {
            const input = document.querySelector('.select2-container--open .select2-search__field');
            if (input) input.focus();
        }, 100);
    });

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>