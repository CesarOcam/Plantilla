<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');

$stmt = $con->prepare("
    SELECT Id, Numero, Nombre
    FROM cuentas
    WHERE Activo = 1
    AND EmpresaId = 2
    AND (
        (SUBSTRING_INDEX(Numero, '-', 1) = '113' AND Numero LIKE '113-%')
    )
    ORDER BY Nombre
");

$stmt->execute();
$subcuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $con->prepare("
    SELECT Id, Nombre 
    FROM beneficiarios
    ORDER BY Nombre
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
        <title>Kardex</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- SweetAlert -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Select2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">

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
            <div class="card-header">
                <div class="d-flex flex-column mb-3">
                    <div class="row m-3 mb-0">
                        <div class="col-1 d-flex flex-column">
                            <label for="statusInput" class="form-label small mb-0">STATUS:</label>
                            <select id="statusInput" class="form-select rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por status">
                                <option value="">TODOS</option>
                                <option value="1">VIGENTE</option>
                                <option value="2">PAGADA</option>
                            </select>
                        </div>

                        <div class="col-1 d-flex flex-column">
                            <label for="fechaDesdeInput" class="form-label small mb-0">FECHA DESDE:</label>
                            <input type="date" id="fechaDesdeInput"
                                class="form-control rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por fecha desde">
                        </div>

                        <div class="col-1 d-flex flex-column">
                            <label for="fechaHastaInput" class="form-label small mb-0">FECHA HASTA:</label>
                            <input type="date" id="fechaHastaInput"
                                class="form-control rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por fecha hasta">
                        </div>

                        <div class="col-1 d-flex flex-column">
                            <label for="numInput" class="form-label small mb-0">NÚMERO:</label>
                            <input type="text" id="numInput" class="form-control rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por póliza">
                        </div>
                        <!-- Botones -->
                        <div class="col-3 d-flex align-items-end justify-content-start gap-2">
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-secondary rounded-0"
                                    id="btn_buscar">Buscar</button>
                            </div>
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-outline-secondary rounded-0"
                                    id="btn_limpiar">Limpiar</button>
                            </div>
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-outline-secondary rounded-0"
                                    id="btn_pagar">Pagar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mb-5" style="border-top: 2px solid #000;">

                <div id="tabla-kardex-container">
                    <?php include('../../modulos/consultas/tabla_kardex.php'); ?>
                </div>

            </div>

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

    <!-- Tu script personalizado -->
    <script>
        flatpickr("#Fecha", {
            enableTime: true,
            time_24hr: true,
            enableSeconds: true,
            dateFormat: "Y-m-d H:i:s", 
            defaultDate: new Date()
        });

        document.getElementById('Fecha').value = document.getElementById('Fecha')._flatpickr.input.value;

        document.addEventListener('DOMContentLoaded', function () {
            const selectBeneficiario = document.getElementById("selectBeneficiario");
            const selectCuenta = document.getElementById("selectCuentaContable");
            if (selectCuenta) {
                $('#selectBeneficiario').select2({
                    width: '100%',
                    placeholder: "Selecciona una beneficiario",
                    allowClear: false,
                    dropdownParent: $('#modalPago')
                });
            }
            if (selectCuenta) {
                $('#selectCuentaContable').select2({
                    width: '100%',
                    placeholder: "Selecciona una cuenta",
                    allowClear: false,
                    dropdownParent: $('#modalPago')
                });
            }

            const btnBuscar = document.getElementById("btn_buscar");
            if (btnBuscar) {
                btnBuscar.addEventListener("click", function () {
                    const status = document.getElementById("statusInput").value;
                    const fechaDesde = document.getElementById("fechaDesdeInput").value;
                    const fechaHasta = document.getElementById("fechaHastaInput").value;
                    const num = document.getElementById("numInput").value;

                    const params = new URLSearchParams({
                        status,
                        fecha_desde: fechaDesde,
                        fecha_hasta: fechaHasta,
                        num
                    });

                    const xhr = new XMLHttpRequest();
                    xhr.open("GET", "../../modulos/consultas/tabla_kardex.php?" + params.toString(), true);
                    xhr.onload = function () {
                        if (this.status === 200) {
                            document.getElementById("tabla-kardex-container").innerHTML = this.responseText;
                        }
                    };
                    xhr.send();
                });
            }

            const btnLimpiar = document.getElementById("btn_limpiar");
            if (btnLimpiar) {
                btnLimpiar.addEventListener("click", function () {
                    document.getElementById("statusInput").value = "";
                    document.getElementById("fechaDesdeInput").value = "";
                    document.getElementById("fechaHastaInput").value = "";
                    document.getElementById("numInput").value = "";

                    if (btnBuscar) btnBuscar.click(); // recargar con filtros vacíos
                });
            }
        });
    </script>

    <script src="../../../js/pagar_kardex/pagar_kardex.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>

</body>

</html>