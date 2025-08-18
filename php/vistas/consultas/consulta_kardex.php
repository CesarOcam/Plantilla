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
    ORDER BY Nombre ASC
");
$stmt->execute();
$beneficiario = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $con->prepare("
    SELECT id2201aduanas, nombre_corto_aduana 
    FROM 2201aduanas 
    WHERE nombre_corto_aduana IS NOT NULL
    AND TRIM(nombre_corto_aduana) <> ''
    AND nombre_corto_aduana NOT IN ('ACAPULCO', 'CHIHUAHUA', 'CIUDAD HIDALGO', 'TUXPAN')

    ORDER BY nombre_corto_aduana
");
$stmt->execute();
$aduanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $con->prepare("SELECT id01clientes_exportadores, razonSocial_exportador
                       FROM 01clientes_exportadores 
                       ORDER BY razonSocial_exportador");
$stmt->execute();
$exp = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">
</head>

<body class="cat-clientes">
    <?php
    include_once __DIR__ . '/../../../config.php';
    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>

    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <div class="card-header">
                <div class="d-flex flex-column mb-3">
                    <div class="row m-3 mb-0">
                        <div class="row mb-0">
                            <div class="col-2 d-flex flex-column">
                                <label for="aduanaInput" name="aduana" class="form-label small mb-0">ADUANA:</label>
                                <select id="aduanaInput" class="form-select rounded-0 border-0 border-bottom"
                                    style="background-color: transparent; cursor:pointer;">
                                    <option value="" selected>TODOS</option>
                                    <?php foreach ($aduanas as $aduana): ?>
                                        <option value="<?php echo $aduana['id2201aduanas']; ?>">
                                            <?php echo $aduana['nombre_corto_aduana']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2 d-flex flex-column">
                                <label for="statusInput" class="form-label small mb-0">STATUS:</label>
                                <select id="statusInput" class="form-select rounded-0 border-0 border-bottom"
                                    style="background-color: transparent; cursor:pointer;"
                                    aria-label="Filtrar por status">
                                    <option value="">TODOS</option>
                                    <option value="1">VIGENTE</option>
                                    <option value="2">PAGADA</option>
                                </select>
                            </div>

                            <div class="col-2 d-flex flex-column">
                                <label for="fechaDesdeInput" class="form-label small mb-0">FECHA DESDE:</label>
                                <input type="date" id="fechaDesdeInput"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha desde">
                            </div>

                            <div class="col-2 d-flex flex-column">
                                <label for="fechaHastaInput" class="form-label small mb-0">FECHA HASTA:</label>
                                <input type="date" id="fechaHastaInput"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha hasta">
                            </div>

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
                                <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                    <button type="button" class="btn btn-outline-secondary rounded-0"
                                        id="btn_exportar">Exportar</button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-0 pt-0">
                            <div class="col-1 d-flex flex-column">
                                <label for="numInput" class="form-label small mb-0">NÚMERO:</label>
                                <input type="text" id="numInput" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" aria-label="Filtrar por póliza">
                            </div>
                            <div class="col-1 d-flex flex-column">
                                <label for="referenciaInput" name='referencia'
                                    class="form-label small mb-0">REFERENCIA:</label>
                                <input type="text" id="referenciaInput"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" aria-label="Filtrar por póliza">
                            </div>
                            <div class="col-1 d-flex flex-column">
                                <label for="comprobacionInput" class="form-label small mb-0">COMPROBACION:</label>
                                <input type="text" id="comprobacionInput"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" aria-label="Filtrar por póliza">
                            </div>
                            <div class="col-3 d-flex flex-column">
                                <label for="comprobacionInput" class="form-label small mb-0">LOGÍSTICO:</label>
                                <select id="logistico-select" name="logistico"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Logístico *</option>
                                    <?php foreach ($exp as $item): ?>
                                        <option value="<?php echo $item['razonSocial_exportador']; ?>">
                                            <?php echo $item['razonSocial_exportador']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2 d-flex flex-column">
                                <label for="tipoInput" class="form-label small mb-0">TIPO DE CONSULTA:</label>
                                <select id="tipoInput" class="form-select rounded-0 border-0 border-bottom"
                                    style="background-color: transparent; cursor:pointer;"
                                    aria-label="Filtrar por status">
                                    <option value="1">DETALLADA</option>
                                    <option value="2">POR LOGÍSTICO</option>
                                </select>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Inicializar fecha con flatpickr
            flatpickr("#Fecha", {
                enableTime: true,
                time_24hr: true,
                enableSeconds: true,
                dateFormat: "Y-m-d H:i:s",
                defaultDate: new Date()
            });

            // Inicializar Select2
            $('#selectBeneficiario').select2({
                width: '100%',
                placeholder: "Selecciona un beneficiario",
                allowClear: false,
                dropdownParent: $('#modalPago')
            });

            $('#selectCuentaContable').select2({
                width: '100%',
                placeholder: "Selecciona una cuenta",
                allowClear: false,
                dropdownParent: $('#modalPago')
            });



            const tipoInput = document.getElementById("tipoInput");

            tipoInput.addEventListener("change", () => {
                const isLogistico = tipoInput.value === "2";

                // Lista de inputs que quieres bloquear/desbloquear
                const inputsParaBloquear = [
                    "statusInput",
                    "fechaDesdeInput",
                    "fechaHastaInput",
                    "numInput",
                    "aduanaInput",
                    "referenciaInput",
                    "comprobacionInput",
                    "logisticoInput",
                ];

                inputsParaBloquear.forEach(id => {
                    const input = document.getElementById(id);
                    if (input) {
                        input.disabled = isLogistico;  // bloquear si tipo=2, desbloquear si no
                        if (isLogistico) input.value = ''; // Opcional: limpia el valor al bloquear
                    }
                });

                // Opcional: si quieres limpiar también el contenido de la tabla al cambiar
                filtrarKardex();
            });


            function initSelect2(id, placeholder) {
                $(id).select2({
                    placeholder: placeholder,
                    allowClear: false,
                    width: '100%'
                });
            }
            initSelect2('#logistico-select', 'Logístico *');
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    let input = document.querySelector('.select2-container--open .select2-search__field');
                    if (input) input.focus();
                }, 100); // pequeño delay para asegurar que el input exista
            });

            // Función para cargar tabla filtrada
            // Función para cargar tabla filtrada
            function filtrarKardex() {
                const tipo = document.getElementById("tipoInput").value;
                const logistico = document.getElementById("logistico-select").value || '';

                const params = new URLSearchParams({
                    status: document.getElementById("statusInput").value || '',
                    fecha_desde: document.getElementById("fechaDesdeInput").value || '',
                    fecha_hasta: document.getElementById("fechaHastaInput").value || '',
                    num: document.getElementById("numInput").value || '',
                    aduana: document.getElementById("aduanaInput").value || '',
                    referencia: document.getElementById("referenciaInput").value || '',
                    logistico: logistico,
                    comprobacion: document.getElementById("comprobacionInput").value || '',
                    tipo: tipo,
                });

                const url = tipo === "2"
                    ? "../../modulos/consultas/tabla_kardex_log.php?" + params.toString()
                    : "../../modulos/consultas/tabla_kardex.php?" + params.toString();

                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById("tabla-kardex-container").innerHTML = html;
                        inicializarEventosTabla();

                        // 🔹 Habilitar el botón Exportar
                        const btnExportar = document.getElementById("btn_exportar");
                        if (logistico !== '') {
                            btnExportar.disabled = false;
                            btnExportar.onclick = () => {
                                 window.location.href = "../../vistas/reportes/estado_cuentas_kardex.php?logistico=" + encodeURIComponent(logistico);
                            };
                        } else {
                            btnExportar.disabled = true;
                            btnExportar.onclick = null;
                        }
                    })
                    .catch(err => console.error("Error al cargar la tabla:", err));
            }

            // Botón buscar
            const btnBuscar = document.getElementById("btn_buscar");
            if (btnBuscar) {
                btnBuscar.addEventListener("click", filtrarKardex);
            }


            // Botón limpiar
            const btnLimpiar = document.getElementById("btn_limpiar");
            if (btnLimpiar) {
                btnLimpiar.addEventListener("click", function () {
                    document.getElementById("statusInput").value = '';
                    document.getElementById("fechaDesdeInput").value = '';
                    document.getElementById("fechaHastaInput").value = '';
                    document.getElementById("numInput").value = '';
                    document.getElementById("aduanaInput").value = '';
                    document.getElementById("referenciaInput").value = '';
                    document.getElementById("logistico-select").value = '';
                    filtrarKardex();
                });
            }
        });
    </script>
    <script src="../../../js/pagar_kardex/pagar_kardex.js"></script>
</body>

</html>