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
        <title>Facturas por Pagar</title>
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
            <form id="form_factura_pp" method="POST">
                <div class="card-header">
                    <div class="d-flex flex-column mb-3">
                        <div class="row m-3 mb-0 align-items-end">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label for="subcuentaInput" class="form-label small mb-1">SUBCUENTA</label>
                                <select id="subcuentaInput" class="form-select select2" style="width: 100%;">
                                    <option value="">Seleccione una subcuenta</option>
                                    <?php
                                    $stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas WHERE Numero LIKE '216-%' ORDER BY Numero ASC");
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
                                    style="background-color: transparent;" placeholder="Fecha Desde">
                            </div>

                            <div class="col-md-2 col-sm-6 mb-2 position-relative">
                                <label for="fechaHastaInput" class="form-label small mb-1">Fecha Hasta:</label>
                                <i class="bi bi-calendar-week position-absolute"
                                    style="left: 10px; top: 36px; z-index: 10; color: gray;"></i>
                                <input id="fechaHastaInput" name="fechaHastaInput" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Fecha Hasta">
                            </div>

                            <div class="col-md-4 col-sm-6 mb-2 d-flex gap-2">
                                <button type="button" class="btn btn-secondary rounded-0 w-100"
                                    id="btn_buscar">Consultar</button>
                                <button type="button" class="btn btn-outline-secondary rounded-0 w-100"
                                    id="btn_limpiar">Limpiar</button>
                                <!--<button type="button" class="btn btn-outline-secondary rounded-0 w-100"
                                    id="btn_correo">Correo</button>-->
                                <button type="button" class="btn btn-outline-secondary rounded-0 w-100" id="btn_pagar"
                                    disabled>Pago de Cuentas</button>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-5" style="border-top: 2px solid #000;">
                    <div id="tabla-pp-container">
                        <?php include('../../modulos/consultas/tabla_facturas_cuentas.php'); ?>
                    </div>

                </div>
            </form>
            <div class="card-body">

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-0 custom-modal-height">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagoLabel">Selecciona un beneficiario y una cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formPago">
                        <div class="mb-3">
                            <label for="selectBeneficiario" class="form-label">Beneficiario</label>
                            <select class="form-select" id="selectBeneficiario" name="beneficiario" required>
                                <option value="" selected>Beneficiario</option>
                                <?php foreach ($beneficiario as $beneficiario): ?>
                                    <option value="<?php echo $beneficiario['Id']; ?>">
                                        <?php echo $beneficiario['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="selectCuentaContable" class="form-label">Cuenta Contable</label>
                            <select class="form-select" id="selectCuentaContable" name="subcuenta" required>
                                <option value="">Selecciona una cuenta</option>
                                <?php foreach ($subcuentas as $cuenta): ?>
                                    <option value="<?= $cuenta['Id'] ?>">
                                        <?= htmlspecialchars($cuenta['Numero'] . ' - ' . $cuenta['Nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <i class="bi bi-calendar-week"
                                style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                            <input id="Fecha" name="fecha" type="text"
                                class="form-control ps-4 rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" placeholder="Fecha y Hora">
                        </div>

                        <!-- INPUTS OCULTOS -->
                        <input type="hidden" id="inputIds" name="ids">
                        <input type="hidden" id="inputTotal" name="total">

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="formPago" class="btn btn-outline-success rounded-0">
                        <i class="bi bi-check-circle-fill me-1"></i> Pagar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="spinnerOverlay" style="
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.4);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: white;
        font-size: 1.2rem;">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;"></div>
        <div style="margin-top: 1rem;">Enviando correo, por favor espere...</div>
    </div>

    <script src="../../../js/facturas_Pp.js"></script>
    <script src="../../../js/pagar_pp/pagar_pp.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>

</body>

</html>