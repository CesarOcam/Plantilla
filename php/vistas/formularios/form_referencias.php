<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');

// Si es petición AJAX para recintos
if (isset($_GET['aduanaNombre'])) {
    $aduanaNombre = trim($_GET['aduanaNombre']);

    if (strtoupper($aduanaNombre) === 'CDMX') {
        $aduanaNombre = 'AEROPUERTO INTERNACIONAL DE LA CIUDAD DE MÉXICO';
    }

    $stmt = $con->prepare("
        SELECT r.id2206_recintos_fiscalizados, r.recintoFiscalizado, r.nombre_conocido_recinto
        FROM 2206_recintos_fiscalizados r
        INNER JOIN (
            SELECT MIN(id2206_recintos_fiscalizados) AS id_min
            FROM 2206_recintos_fiscalizados
            WHERE aduanaFiscalizada IS NOT NULL
            AND nombre_conocido_recinto IS NOT NULL
            AND aduanaFiscalizada LIKE :aduanaNombre
            GROUP BY nombre_conocido_recinto
        ) sub ON r.id2206_recintos_fiscalizados = sub.id_min
        ORDER BY r.nombre_conocido_recinto
    ");

    $stmt->execute(['aduanaNombre' => "%$aduanaNombre%"]);
    $recintos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($recintos);
    exit;
}

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

// RECINTOS
$stmt = $con->prepare("SELECT id2206_recintos_fiscalizados, nombre_conocido_recinto
                       FROM 2206_recintos_fiscalizados 
                       WHERE nombre_conocido_recinto IS NOT NULL AND nombre_conocido_recinto != ''
                       ORDER BY nombre_conocido_recinto");
$stmt->execute();
$recinto = $stmt->fetchAll(PDO::FETCH_ASSOC);

// NAVIERAS
$stmt = $con->prepare("SELECT idtransportista, nombre_transportista
                       FROM transportista 
                       WHERE nombre_transportista IS NOT NULL AND nombre_transportista != ''
                       ORDER BY nombre_transportista");
$stmt->execute();
$naviera = $stmt->fetchAll(PDO::FETCH_ASSOC);

// BUQUES
$stmt = $con->prepare("SELECT idtransporte, identificacion
                       FROM transporte 
                       WHERE identificacion IS NOT NULL AND identificacion != ''
                       ORDER BY identificacion");
$stmt->execute();
$buque = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consolidadoras
$stmt = $con->prepare("SELECT id_consolidadora, denominacion_consolidadora
                       FROM consolidadoras 
                       WHERE denominacion_consolidadora IS NOT NULL AND denominacion_consolidadora != ''
                       ORDER BY denominacion_consolidadora");
$stmt->execute();
$consolidadora = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Obtener referencias para select referencias
$stm = $con->prepare("SELECT Id, Numero FROM conta_referencias WHERE Status IS NOT NULL AND Status != 0 ORDER BY Numero ASC");
$stm->execute();
$referencias = $stm->fetchAll();

//CONTENEDORES
$stmt = $con->prepare("SELECT id2210_tipo_contenedor, descripcion_contenedor FROM 2210_tipo_contenedor");
$stmt->execute();
$tiposContenedor = $stmt->fetchAll(PDO::FETCH_ASSOC);

//CLAVE PEDIMENTO
$stmt = $con->prepare("SELECT id2202clave_pedimento, claveCve FROM 2202clavepedimento");
$stmt->execute();
$clavePedimento = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Referencia</title>
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
        <form id="form_Referencia" method="POST" enctype="multipart/form-data">
            <div class="card-header formulario_referencia">
                <h5 class="mt-3">Nueva Referencia</h5>

                <!-- Tabs -->
                <ul class="nav nav-tabs mt-3" id="clienteTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos"
                            type="button" role="tab">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contenedores-tab" data-bs-toggle="tab"
                            data-bs-target="#contenedores" type="button" role="tab">Contenedores</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="opciones-tab" data-bs-toggle="tab" data-bs-target="#opciones"
                            type="button" role="tab">Documentos</button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-4" id="clienteTabsContent">

                    <!-- Datos Generales -->
                    <div class="tab-pane fade show active" id="datos" role="tabpanel">
                        <div class="row">
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4"
                                style="background-color: transparent;">
                                <input id="input-referencia" name="referencia" type="text" maxlength="50"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Referencia">
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="aduana-select" name="aduana">
                                    <option value="" selected disabled>-- Selecciona una aduana --</option>
                                    <?php foreach ($aduanas as $aduana): ?>
                                        <option value="<?= htmlspecialchars($aduana['id2201aduanas']) ?>">
                                            <?= htmlspecialchars($aduana['nombre_corto_aduana']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="exportador-select" name="exportador"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Exportador *</option>
                                    <?php foreach ($exp as $item): ?>
                                        <?php if ($item['tipo_cliente'] == 0 || $item['tipo_cliente'] == 2): ?>
                                            <option value="<?= $item['id01clientes_exportadores'] ?>">
                                                <?= htmlspecialchars($item['razonSocial_exportador']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="logistico-select" name="logistico"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Logístico *</option>
                                    <?php foreach ($exp as $item): ?>
                                        <?php if ($item['tipo_cliente'] == 1 || $item['tipo_cliente'] == 2): ?>
                                            <option value="<?= $item['id01clientes_exportadores'] ?>">
                                                <?= htmlspecialchars($item['razonSocial_exportador']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                                <input name="mercancia" type="text" maxlength="50"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Identificación de Mercancía">
                            </div>

                            <!-- FILA 2 -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="marcas" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Marcas">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="pedimento" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Pedimento">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="clave-select" name="clave"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Clave Pedimento</option>
                                    <?php foreach ($clavePedimento as $item): ?>
                                        <option value="<?php echo $item['id2202clave_pedimento']; ?>">
                                            <?php echo $item['claveCve']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <input name="peso" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Peso Bruto">
                            </div>
                            <!-- FILA 3 -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="bultos" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Cantidad y Bultos">
                            </div>

                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="consolidadora-select" name="consolidadora"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Consolidadora</option>
                                    <?php foreach ($consolidadora as $consolidadora): ?>
                                        <option value="<?php echo $consolidadora['id_consolidadora']; ?>">
                                            <?php echo $consolidadora['denominacion_consolidadora']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="resultado_mod-select" name="resultado_mod"
                                    class="form-control rounded-0 border-0 border-bottom text-muted" disabled>
                                    <option value="" selected disabled>Resultado de Modulación</option>
                                    <option value="1">Verde</option>
                                    <option value="2">Rojo</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="recinto-select" name="recinto" disabled>
                                    <option value="" selected disabled>Seleccione una aduana primero</option>
                                </select>
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="naviera-select" name="naviera"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Naviera</option>
                                    <?php foreach ($naviera as $item): ?>
                                        <option value="<?php echo $item['idtransportista']; ?>">
                                            <?php echo $item['nombre_transportista']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4 position-relative">
                                <i class="bi bi-calendar-week"
                                    style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                                <input id="cierre_doc" name="cierre_doc" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Cierre de Documentos">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4 position-relative">
                                <i class="bi bi-calendar-week"
                                    style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                                <input id="fecha_pago" name="fecha_pago" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Fecha Pago">
                            </div>
                            <!-- Tab2 Row2 -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="buque-select" name="buque"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Buque</option>
                                    <?php foreach ($buque as $item): ?>
                                        <option value="<?php echo $item['idtransporte']; ?>">
                                            <?php echo $item['identificacion']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="booking" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Booking">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4 position-relative">
                                <i class="bi bi-calendar-week"
                                    style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                                <input id="cierre_desp" name="cierre_desp" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Cierre de Despacho">
                            </div>
                            <!-- Hora de Despacho con icono y timepicker -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4 position-relative">
                                <i class="bi bi-clock"
                                    style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                                <input id="hora_desp" name="hora_desp" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Hora de Despacho">
                            </div>

                            <!-- Tab2 Row3 -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="viaje" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Viaje">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="su_referencia" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Referencia Externa">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4 position-relative">
                                <i class="bi bi-calendar-week"
                                    style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                                <input id="fecha_doc" name="fecha_doc" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Fecha de Documentado">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4 position-relative">
                                <i class="bi bi-calendar-week"
                                    style="position: absolute; left: 10px; z-index: 10; color: gray;"></i>
                                <input id="fecha_eta" name="fecha_eta" type="text"
                                    class="form-control ps-4 rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;"
                                    placeholder="Fecha Estimada de Llegada (ETA)">
                            </div>
                            <!-- Tab2 Row3 -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="puerto_desc" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Puerto de Descarga">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="puerto_dest" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Puerto de Destino">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10 col-sm-6 d-flex align-items-center mt-4">
                                <textarea name="comentarios" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent; resize: none;" placeholder="Comentarios"
                                    rows="4"></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="tab-pane fade" id="contenedores" role="tabpanel">
                        <div class="row">

                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-secondary text-white fw-bold">
                                    <i class="bi bi-box-fill me-2"></i> Contenedores Agregados
                                </div>
                                <div class="card-body p-3">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered tabla-contenedores"
                                            id="tabla-contenedores">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th></th>
                                                    <th>Contenedor</th>
                                                    <th>Tipo</th>
                                                    <th>Sello</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Filas se agregarán dinámicamente -->
                                            </tbody>
                                        </table>
                                        <button type="button" id="btn-nuevo-contenedor"
                                            class="btn btn-outline-secondary mb-3 rounded-0">
                                            <i class="bi bi-plus-circle me-1"></i> Nuevo Contenedor
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opciones -->
                    <div class="tab-pane fade" id="opciones" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-12 mt-5">
                                <button type="button" class="btn btn-outline-secondary rounded-0 btn-md"
                                    data-bs-toggle="modal" data-bs-target="#modalDocumentos">
                                    <i class="bi bi-upload me-1"></i> Subir Documentos
                                </button>

                                <!-- Tabla de documentos cargados -->
                                <div class="table-responsive mt-3">
                                    <table
                                        class="table table-sm tabla-partidas-estilo tabla-documentos table-hover align-middle">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Tipo</th>
                                                <th>Tamaño</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla-documentos-body">
                                            <!-- Documentos cargados se agregarán aquí dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="button" class="btn btn-outline-danger rounded-0"
                            onclick="window.location.href='../../vistas/consultas/consulta_referencia.php'">Salir</button>
                    </div>
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="submit" class="btn btn-secondary rounded-0" id="btn_guardar">Guardar</button>
                    </div>
                </div>
            </div>

            <!-- MODAL DE DOCUMENTOS -->
            <div class="modal fade" id="modalDocumentos" tabindex="-1" aria-labelledby="modalDocumentosLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDocumentosLabel">Subir Documentos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">

                            <!-- Área de arrastre con ícono nube y contenedor de previsualización -->
                            <div id="dropZone"
                                class="border border-2 border-primary rounded p-5 mb-3 text-center bg-light"
                                style="cursor: pointer; position: relative; min-height: 250px;">
                                <!-- Contenido por defecto: ícono y texto -->
                                <div id="dropZoneDefault"
                                    class="d-flex flex-column align-items-center justify-content-center h-100">
                                    <i class="bi bi-cloud-arrow-up-fill" style="font-size: 4rem; color: #0d6efd;"></i>
                                    <p class="text-muted mb-0 mt-3">Arrastra los documentos aquí</p>
                                </div>
                                <!-- Contenedor para previsualización (oculto al principio) -->
                                <div id="previewContainer" class="row row-cols-1 row-cols-md-3 g-3 d-none"
                                    style="overflow-y: auto; max-height: 350px; margin-top: 1rem;">
                                    <!-- Vistas previas dinámicas -->
                                </div>
                            </div>
                            <!-- Botón para seleccionar archivos debajo del recuadro con icono carpeta -->
                            <div class="text-center mb-3">
                                <button type="button" class="btn btn-outline-primary" id="btnBuscarArchivos">
                                    <i class="bi bi-folder-fill me-2"></i> Seleccionar desde carpeta
                                </button>
                                <input type="file" id="documentosInput" name="documentos[]" multiple hidden>
                            </div>

                            <!-- Botón final -->
                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-primary" id="btnAgregarDocs">Agregar a la
                                    referencia</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    const tiposContenedor = <?= json_encode($tiposContenedor) ?>;

    const botonGuardar = document.getElementById('btn_guardar');

    $(document).ready(function () {
        function initSelect2(id, placeholder) {
            $(id).select2({
                placeholder: placeholder,
                allowClear: false,
                width: '100%'
            });
        }

        initSelect2('#referencia-select', 'Referencia');
        initSelect2('#aduana-select', 'Aduana');
        initSelect2('#exportador-select', 'Exportador *');
        initSelect2('#logistico-select', 'Logístico *');
        initSelect2('#recinto-select', 'Recinto');
        initSelect2('#clave-select', 'Clave Pedimento');
        initSelect2('#naviera-select', 'Naviera');
        initSelect2('#buque-select', 'Buque');
        initSelect2('#consolidadora-select', 'Consolidadora');
        initSelect2('#resultado_mod-select', 'Resultado de Modulación');

        // Coloca automáticamente el cursor en la caja de búsqueda al abrir cualquier select2
        $(document).on('select2:open', () => {
            setTimeout(() => {
                let input = document.querySelector('.select2-container--open .select2-search__field');
                if (input) input.focus();
            }, 100); // pequeño delay para asegurar que el input exista
        });


        let contador = 1;

        const tablaContenedores = document.getElementById("tabla-contenedores").querySelector("tbody");
        const botonGuardar = document.getElementById("btn_guardar");

        function verificarCodificacion(contenedor) {
            const tabla = {
                'A': 10, 'B': 12, 'C': 13, 'D': 14, 'E': 15,
                'F': 16, 'G': 17, 'H': 18, 'I': 19, 'J': 20,
                'K': 21, 'L': 23, 'M': 24, 'N': 25, 'O': 26,
                'P': 27, 'Q': 28, 'R': 29, 'S': 30, 'T': 31,
                'U': 32, 'V': 34, 'W': 35, 'X': 36, 'Y': 37,
                'Z': 38
            };

            if (!/^[A-Z]{4}\d{7}$/.test(contenedor)) return false;

            let suma = 0;
            for (let i = 0; i < 10; i++) {
                const char = contenedor[i];
                let valor = isNaN(char) ? tabla[char] : parseInt(char);
                if (valor === undefined || isNaN(valor)) return false;
                suma += valor * Math.pow(2, i);
            }

            const checkDigit = suma % 11;
            const digitoCalculado = checkDigit === 10 ? 0 : checkDigit;
            const digitoIngresado = parseInt(contenedor[10]);

            return digitoCalculado === digitoIngresado;
        }

        document.getElementById('btn-nuevo-contenedor').addEventListener('click', function () {
            const rowId = `fila-${contador}`;
            const inputId = `input-contenedor-${contador}`;
            const mensajeId = `mensajeContenedor-${contador}`;
            const iconoId = `iconoValidacion-${contador}`;

            const fila = `
            <tr id="${rowId}" class="text-center">
                <td class="text-center align-middle"><i class="bi bi-box-fill fs-4 me-2"></i></td>
                <td>
                    <div class="position-relative">
                        <input type="text" id="${inputId}" name="contenedor[]" class="form-control ps-4 rounded-0 border-0 border-bottom text-center" placeholder="Ingrese el código" maxlength="11">
                        <i id="${iconoId}" class="bi position-absolute top-50 end-0 translate-middle-y me-2"></i>
                        <small id="${mensajeId}" class="form-text ms-1 mt-1"></small>
                    </div>
                </td>
                <td class="text-center">
                    <select name="tipo[]" class="form-select tipo-select form-control ps-4 rounded-0 border-0 border-bottom text-center" style="width: 100%;">
                        <option value="">Seleccione un tipo</option>
                        ${tiposContenedor.map(tc => `<option value="${tc.id2210_tipo_contenedor}">${tc.id2210_tipo_contenedor} - ${tc.descripcion_contenedor}</option>`).join('')}
                    </select>
                </td>
                <td class="text-center"><input type="text" name="sello[]" class="form-control ps-4 rounded-0 border-0 border-bottom text-center" placeholder="Ingrese el sello"></td>
                <td class="text-center align-middle">
                    <button type="button" id="btn-eliminar" class="btn btn-md btn-danger rounded-0">Eliminar</button>
                </td>
            </tr>`;

            tablaContenedores.insertAdjacentHTML('beforeend', fila);

            agregarValidacion(inputId, mensajeId, iconoId);

            $('.tipo-select').last().select2({
                placeholder: 'Seleccione un tipo',
                allowClear: false,
                width: '100%'
            });

            contador++;
        });

        function agregarValidacion(inputId, mensajeId, iconoId) {
            const inputContenedor = document.getElementById(inputId);
            const mensajeContenedor = document.getElementById(mensajeId);
            const icono = document.getElementById(iconoId);

            // Busca ícono que puede ser box-fill o box-seam en la primera celda
            const fila = inputContenedor.closest("tr");
            const iconoContenedor = fila.querySelector(".bi-box-fill, .bi-box-seam");

            inputContenedor.addEventListener('input', function () {
                const valor = this.value.toUpperCase();
                this.value = valor;

                inputContenedor.classList.remove('border-success', 'border-danger', 'border-secondary');
                mensajeContenedor.classList.remove('text-success', 'text-danger', 'text-muted');
                icono.className = ''; // limpiar icono
                iconoContenedor.classList.remove('text-success', 'text-danger', 'text-muted');

                if (valor.length === 0) {
                    botonGuardar.disabled = false;
                    mensajeContenedor.textContent = "";
                    iconoContenedor.classList.remove('bi-box-fill', 'text-success', 'text-danger', 'text-muted');
                    iconoContenedor.classList.add('bi-box-fill');
                } else if (valor.length === 11) {
                    if (verificarCodificacion(valor)) {
                        mensajeContenedor.textContent = ""; // sin texto
                        inputContenedor.classList.add('border-success');
                        iconoContenedor.classList.remove('bi-box-fill', 'text-danger', 'text-muted');
                        iconoContenedor.classList.add('bi-box-fill', 'text-success');
                        botonGuardar.disabled = false;
                    } else {
                        mensajeContenedor.textContent = "Contenedor inválido";
                        mensajeContenedor.classList.add('text-danger');
                        inputContenedor.classList.add('border-danger');
                        icono.className = ''; // limpiar icono validación al lado del input
                        icono.classList.add('bi', 'bi-x-circle-fill', 'text-danger');

                        iconoContenedor.classList.remove('bi-box-fill', 'text-success');
                        iconoContenedor.classList.add('bi-box-fill', 'text-danger');

                        botonGuardar.disabled = true;
                    }
                } else {
                    // ⚠️ Incompleto
                    mensajeContenedor.textContent = "Debe tener 11 caracteres";
                    mensajeContenedor.classList.add('text-muted');
                    inputContenedor.classList.add('border-secondary');
                    icono.className = '';
                    icono.classList.add('bi', 'bi-exclamation-circle', 'text-muted');

                    iconoContenedor.classList.remove('bi-box-fill', 'text-success', 'text-danger');
                    iconoContenedor.classList.add('bi-box-fill', 'text-muted');

                    botonGuardar.disabled = true;
                }
            });
        }

        tablaContenedores.addEventListener('click', function (e) {
            if (e.target.closest('#btn-eliminar')) {
                const filaEliminada = e.target.closest('tr');
                filaEliminada.remove();
                actualizarNumeracion(); // si quieres renumerar IDs

                // Revalidar inputs en filas restantes para refrescar iconos
                const filas = tablaContenedores.querySelectorAll('tr');
                filas.forEach(fila => {
                    const input = fila.querySelector('input[name="contenedor[]"]');
                    if (input) {
                        // Forzar evento input para actualizar iconos
                        input.dispatchEvent(new Event('input'));
                    }
                });
            }
        });

        // Evento change aquí, dentro del ready
        $('#aduana-select').on('change', function () {
            const aduanaNombre = this.options[this.selectedIndex].text;
            const recintoSelect = $('#recinto-select');

            // Mostrar mensaje de carga dentro del select (opción)
            recintoSelect.prop('disabled', true);
            recintoSelect.empty().append('<option>Cargando recintos...</option>').trigger('change');

            fetch(`form_referencias.php?aduanaNombre=${encodeURIComponent(aduanaNombre)}`)
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("La respuesta no es JSON válida.");
                    }
                    return response.json();
                })
                .then(data => {
                    recintoSelect.empty(); // Limpiar opciones previas

                    if (data.length > 0) {
                        recintoSelect.append('<option value="" disabled selected>-- Selecciona un recinto --</option>');
                        data.forEach(recinto => {
                            recintoSelect.append(`<option value="${recinto.id2206_recintos_fiscalizados}">${recinto.nombre_conocido_recinto}</option>`);
                        });
                        recintoSelect.prop('disabled', false);
                    } else {
                        recintoSelect.append('<option value="" disabled selected>No hay recintos para esta aduana</option>');
                        recintoSelect.prop('disabled', true);
                    }

                    recintoSelect.trigger('change'); // Refrescar Select2 visualmente
                })
                .catch(error => {
                    console.error('Error al cargar recintos:', error);
                    recintoSelect.empty()
                        .append('<option value="" disabled selected>Error al cargar recintos</option>')
                        .prop('disabled', true)
                        .trigger('change');
                });
        });
    });

    // Inicializar Calendarios
    flatpickr("#cierre_doc", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#cierre_desp", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#fecha_pago", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#hora_desp", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        allowInput: true
    });
    flatpickr("#fecha_doc", {
        dateFormat: "Y-m-d"
    });
    flatpickr("#fecha_eta", {
        dateFormat: "Y-m-d"
    });

    //lÓGICA DEL MODAL
    function obtenerIconoPorExtension(nombreArchivo) {
        const extension = nombreArchivo.split('.').pop().toLowerCase();

        switch (extension) {
            case 'pdf':
                return '<i class="bi bi-file-earmark-pdf text-danger"></i>';
            case 'doc':
            case 'docx':
                return '<i class="bi bi-file-earmark-word text-primary"></i>';
            case 'xls':
            case 'xlsx':
                return '<i class="bi bi-file-earmark-excel text-success"></i>';
            case 'csv':
                return '<i class="bi bi-filetype-csv text-success"></i>';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return '<i class="bi bi-file-earmark-image text-info"></i>';
            case 'zip':
            case 'rar':
                return '<i class="bi bi-file-earmark-zip text-warning"></i>';
            case 'txt':
                return '<i class="bi bi-file-earmark-text text-muted"></i>';
            case 'php':
                return '<i class="bi bi-filetype-php text-purple"></i>';
            default:
                return '<i class="bi bi-file-earmark text-secondary"></i>';
        }
    }

    document.getElementById('btnAgregarDocs').addEventListener('click', function () {
        const tableBody = document.getElementById('tabla-documentos-body');

        archivosCargados.forEach(file => {
            const icono = obtenerIconoPorExtension(file.name);
            const row = document.createElement('tr');
            row.innerHTML = `
            <td> ${icono} ${file.name}</td>
            <td>${file.type || 'Desconocido'}</td>
            <td>${(file.size / 1024).toFixed(2)} KB</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" data-eliminar="true">Eliminar</button>
            </td>
            `;
            tableBody.appendChild(row);
        });


        previewContainer.innerHTML = '';
        previewContainer.classList.add('d-none');
        dropZoneDefault.classList.remove('d-none');
        input.value = '';

        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalDocumentos'));
        modal.hide();
    });

    // Delegación para eliminar fila de la tabla
    document.getElementById('tabla-documentos-body').addEventListener('click', (e) => {
        if (e.target.dataset.eliminar) {
            e.target.closest('tr').remove();
        }
    });


    const dropZone = document.getElementById('dropZone');
    const input = document.getElementById('documentosInput');
    const previewContainer = document.getElementById('previewContainer');
    const dropZoneDefault = document.getElementById('dropZoneDefault');
    const btnBuscarArchivos = document.getElementById('btnBuscarArchivos');

    let archivosCargados = [];

    btnBuscarArchivos.addEventListener('click', () => input.click());
    dropZone.addEventListener('click', () => input.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('bg-primary-subtle');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('bg-primary-subtle');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bg-primary-subtle');
        handleFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', () => handleFiles(input.files));

    function handleFiles(files) {
        console.log('handleFiles recibidos:', files);
        Array.from(files).forEach(file => {
            console.log('Archivo agregado:', file.name);
            archivosCargados.push(file);

            // Mostrar previsualización
            const preview = document.createElement('div');
            preview.classList.add('col');

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'img-fluid rounded border';
                img.style.maxHeight = '150px';
                preview.appendChild(img);

            } else if (file.type === 'application/pdf') {
                preview.innerHTML = `
                < div class="border p-3 text-center rounded bg-white" >
                <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
                <p class="small mt-2">${file.name}</p>
                <iframe src="${URL.createObjectURL(file)}" style="width: 100%; height: 150px;" frameborder="0"></iframe>
            </div > `;

            } else if (
                file.type ===
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ||
                file.type === 'application/vnd.ms-excel'
            ) {
                preview.innerHTML = `
                < div class="border p-3 text-center rounded bg-white" >
                    <i class="bi bi-file-earmark-excel-fill fs-1 text-success"></i>
                    <p class="small mt-2">${file.name}</p>
                    <p class="small text-muted">Previsualización no disponible</p>
                </div > `;

            } else if (file.type.startsWith('text/')) {
                const reader = new FileReader();
                reader.onload = () => {
                    preview.innerHTML = `
                < div class="border p-3 rounded bg-white text-start" style = "max-height: 150px; overflow-y: auto;" >
                    <p class="small fw-bold">${file.name}</p>
                    <pre class="small mb-0">${reader.result.substring(0, 200)}...</pre>
                </div > `;
                };
                reader.readAsText(file);

            } else {
                preview.innerHTML = `
                < div class="border p-3 text-center rounded bg-white" >
                    <i class="bi bi-file-earmark-fill fs-1 text-secondary"></i>
                    <p class="small mt-2">${file.name}</p>
                    <p class="small text-muted">Previsualización no disponible</p>
                </div >`;
            }

            previewContainer.appendChild(preview);
        });

        // Mostrar previsualización y ocultar el contenido por defecto
        previewContainer.classList.remove('d-none');
        dropZoneDefault.classList.add('d-none');
    }


    // Evento para eliminar fila de la tabla (delegación)
    document.getElementById('tabla-documentos-body').addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-eliminar')) {
            e.target.closest('tr').remove();
        }
    });
</script>

<script src="../../../js/guardar_Referencia.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>