<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');
// Obtener aduanas
$stmt = $con->prepare("SELECT id2201aduanas, nombre_corto_aduana 
                       FROM 2201aduanas 
                       WHERE nombre_corto_aduana IS NOT NULL 
                         AND TRIM(nombre_corto_aduana) != '' 
                         AND id2201aduanas IN (25, 74, 81, 91, 119, 124)
                       ORDER BY nombre_corto_aduana");
$stmt->execute();
$aduanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EXPORTADORES Y LOGISTICOS
$stmt = $con->prepare("SELECT id01clientes_exportadores, razonSocial_exportador
                       FROM 01clientes_exportadores 
                       ORDER BY razonSocial_exportador");
$stmt->execute();
$exp = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<body class="consulta-referencias">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Referencias</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
        <!-- jQuery primero -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- SweetAlert2 después -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <!-- Fechas -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- Select2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
                        <div class="col-1 d-flex flex-column">
                            <label for="statusInput" class="form-label small mb-0">STATUS:</label>
                            <select id="statusInput" class="form-select rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por status">
                                <option value="">TODOS</option>
                                <option value="1">EN TRÁFICO</option>
                                <option value="2">EN CONTABILIDAD</option>
                                <option value="3">FACTURADA</option>
                                <option value="4">CANCELADA</option>
                            </select>
                        </div>

                        <div class="col-2 d-flex flex-column">
                            <label for="aduana-select" class="form-label small mb-0">ADUANA:</label>
                            <select id="aduana-select" name="aduana">
                                <option value="todas">TODAS</option> <!-- opción estática -->
                                <?php foreach ($aduanas as $aduana): ?>
                                    <option value="<?= htmlspecialchars($aduana['id2201aduanas']) ?>">
                                        <?= htmlspecialchars($aduana['nombre_corto_aduana']) ?>
                                    </option>
                                <?php endforeach; ?>
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
                            <label for="referenciaInput" class="form-label small mb-0">REFERENCIA:</label>
                            <input type="text" id="referenciaInput"
                                class="form-control rounded-0 border-0 border-bottom"
                                style="background-color: transparent;" aria-label="Filtrar por póliza">
                        </div>

                        <div class="col-4 d-flex flex-column">
                            <label for="logisticoInput" class="form-label small mb-0">LOGISTICO:</label>
                            <select id="logistico-select" name="logistico"
                                class="form-control rounded-0 border-0 border-bottom text-muted">
                                <option value="" selected disabled>Logístico *</option>
                                <?php foreach ($exp as $item): ?>
                                    <option value="<?php echo $item['id01clientes_exportadores']; ?>">
                                        <?php echo $item['razonSocial_exportador']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Botones -->
                        <div class="col-1 d-flex align-items-end justify-content-start gap-2">
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-secondary rounded-0"
                                    id="btn_buscar">Buscar</button>
                            </div>
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-outline-secondary rounded-0"
                                    id="btn_limpiar">Limpiar</button>
                            </div>
                        </div>

                        <!-- Botón "+" a la derecha -->
                        <div class="col d-flex align-items-start justify-content-end mt-3 mb-5">
                            <a href="../formularios/form_referencias.php"
                                class="btn btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px;" title="Agregar nuevo">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <hr class="mb-5" style="border-top: 2px solid #000;">

                <div id="tabla-referencias-container">
                    <!-- Aqui se encuentra la tabla de referencias -->
                </div>

            </div>

            <div class="card-body">

            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            function initSelect2(id, placeholder) {
                $(id).select2({
                    placeholder: placeholder,
                    allowClear: false,
                    width: '100%'
                });
            }

            initSelect2('#aduana-select', 'Selecciona...');
            initSelect2('#logistico-select', 'Selecciona...');

            // Coloca automáticamente el cursor en la caja de búsqueda al abrir cualquier select2
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    let input = document.querySelector('.select2-container--open .select2-search__field');
                    if (input) input.focus();
                }, 100); // pequeño delay para asegurar que el input exista
            });
        });

        document.getElementById("btn_buscar").addEventListener("click", function () {
            const status = document.getElementById("statusInput").value;
            const aduana = document.getElementById("aduana-select").value;
            const fechaDesde = document.getElementById("fechaDesdeInput").value;
            const fechaHasta = document.getElementById("fechaHastaInput").value;
            const referencia = document.getElementById("referenciaInput").value;
            const logistico = document.getElementById("logistico-select").value;

            const params = new URLSearchParams({
                status,
                aduana,
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                referencia,
                logistico
            });

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "../../modulos/consultas/tabla_referencias.php?" + params.toString(), true);
            xhr.onload = function () {
                if (this.status === 200) {
                    document.getElementById("tabla-referencias-container").innerHTML = this.responseText;
                }
            };
            xhr.send();
        });

        // Limpiar filtros
        document.getElementById("btn_limpiar").addEventListener("click", function () {
            document.getElementById("statusInput").value = "";
            document.getElementById("fechaDesdeInput").value = "";
            document.getElementById("fechaHastaInput").value = "";
            document.getElementById("referenciaInput").value = "";

            $('#aduana-select').val(null).trigger('change');
            $('#logistico-select').val(null).trigger('change');

            document.getElementById("btn_buscar").click(); // recargar con filtros vacíos
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>
</body>

</html>