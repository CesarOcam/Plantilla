<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');

// ADUANAS
$stmt = $con->prepare("SELECT id2201aduanas, nombre_corto_aduana 
                       FROM 2201aduanas 
                       WHERE nombre_corto_aduana IS NOT NULL 
                       AND TRIM(nombre_corto_aduana) != '' ORDER BY nombre_corto_aduana");
$stmt->execute();
$aduana = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EXPORTADORES Y LOGISTICOS
$stmt = $con->prepare("SELECT id01clientes_exportadores, razonSocial_exportador
                       FROM 01clientes_exportadores 
                       ORDER BY razonSocial_exportador");
$stmt->execute();
$exp = $stmt->fetchAll(PDO::FETCH_ASSOC);

// RECINTOS
$stmt = $con->prepare("SELECT id2221_recintos, nombre_recinto
                       FROM 2221_recintos 
                       WHERE nombre_recinto IS NOT NULL AND nombre_recinto != ''
                       ORDER BY nombre_recinto");
$stmt->execute();
$recinto = $stmt->fetchAll(PDO::FETCH_ASSOC);


// NAVIERAS
$stmt = $con->prepare("SELECT Id, Nombre
                       FROM navieras 
                       WHERE Nombre IS NOT NULL AND Nombre != ''
                       ORDER BY Nombre");
$stmt->execute();
$naviera = $stmt->fetchAll(PDO::FETCH_ASSOC);

// BUQUES
$stmt = $con->prepare("SELECT Id, Nombre
                       FROM buques 
                       WHERE Nombre IS NOT NULL AND Nombre != ''
                       ORDER BY Nombre");
$stmt->execute();
$buque = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
                <h5>+ Agregar Referencia</h5>

                <!-- Tabs -->
                <ul class="nav nav-tabs mt-3" id="clienteTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos"
                            type="button" role="tab">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="contacto-tab" data-bs-toggle="tab" data-bs-target="#contacto"
                            type="button" role="tab">Otros Datos</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="direccion-tab" data-bs-toggle="tab" data-bs-target="#direccion"
                            type="button" role="tab">Movimientos</button>
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
                            <div class="col-10 col-sm-1 d-flex align-items-center mt-4">
                                <input name="referencia" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Referencia" readonly>
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="aduana-select" name="aduana"
                                    class="form-control rounded-0 border-0 border-bottom text-muted select-align-fix"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha"
                                    aria-describedby="basic-addon1">
                                    <option value="" selected disabled>Aduana</option>
                                    <?php foreach ($aduana as $aduana): ?>
                                        <option value="<?php echo $aduana['id2201aduanas']; ?>">
                                            <?php echo $aduana['nombre_corto_aduana']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="exportador-select" name="exportador"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Exportador *</option>
                                    <?php foreach ($exp as $item): ?>
                                        <option value="<?php echo $item['id01clientes_exportadores']; ?>">
                                            <?php echo $item['razonSocial_exportador']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
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
                            <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                                <input name="mercancia" type="text"
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
                                <input name="clave_pedimento" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Pedimento">
                            </div>
                            <div class="col-10 col-sm-1 d-flex align-items-center mt-4">
                                <input name="peso" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Peso Bruto">
                            </div>
                            <div class="col-10 col-sm-1 d-flex align-items-center mt-4">
                                <input name="cantidad" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Cantidad">
                            </div>

                            <!-- FILA 3 -->
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="bultos" type="text" class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Bultos">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="contenedor" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Contenedor">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="consolidadora" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Consolidadora">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <input name="resultado_mod" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Resultado Modulación">
                            </div>
                        </div>
                    </div>

                    <!-- Contacto -->
                    <div class="tab-pane fade" id="contacto" role="tabpanel">
                        <div class="row">
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="recinto-select" name="recinto"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Recintos</option>
                                    <?php foreach ($recinto as $item): ?>
                                        <option value="<?php echo $item['id2221_recintos']; ?>">
                                            <?php echo $item['nombre_recinto']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                                <select id="naviera-select" name="naviera"
                                    class="form-control rounded-0 border-0 border-bottom text-muted">
                                    <option value="" selected disabled>Recintos</option>
                                    <?php foreach ($naviera as $item): ?>
                                        <option value="<?php echo $item['Id']; ?>">
                                            <?php echo $item['Nombre']; ?>
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
                                        <option value="<?php echo $item['Id']; ?>">
                                            <?php echo $item['Nombre']; ?>
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
                                    style="background-color: transparent;" placeholder="Su Referencia">
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
                            <div class="col-10 col-sm-6 d-flex align-items-center mt-4">
                                <input name="comentarios" type="text"
                                    class="form-control rounded-0 border-0 border-bottom"
                                    style="background-color: transparent;" placeholder="Comentarios">
                            </div>

                        </div>
                    </div>

                    <!-- Dirección -->
                    <div class="tab-pane fade" id="direccion" role="tabpanel">
                        <div class="row">

                        </div>
                    </div>

                    <!-- Opciones -->
                    <div class="tab-pane fade" id="opciones" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-12 mt-5">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#modalDocumentos">
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
                                    tabla</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    
    $(document).ready(function () {
        function initSelect2(id, placeholder) {
            $(id).select2({
                placeholder: placeholder,
                allowClear: true,
                width: '100%'
            });
        }

        initSelect2('#aduana-select', 'Aduana');
        initSelect2('#exportador-select', 'Exportador *');
        initSelect2('#logistico-select', 'Logístico *');
        initSelect2('#recinto-select', 'Recinto');
        initSelect2('#naviera-select', 'Naviera');
        initSelect2('#buque-select', 'Buque');
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
            <td>${icono} ${file.name}</td>
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
        <div class="border p-3 text-center rounded bg-white">
          <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
          <p class="small mt-2">${file.name}</p>
          <iframe src="${URL.createObjectURL(file)}" style="width: 100%; height: 150px;" frameborder="0"></iframe>
        </div>`;

            } else if (
                file.type ===
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ||
                file.type === 'application/vnd.ms-excel'
            ) {
                preview.innerHTML = `
        <div class="border p-3 text-center rounded bg-white">
          <i class="bi bi-file-earmark-excel-fill fs-1 text-success"></i>
          <p class="small mt-2">${file.name}</p>
          <p class="small text-muted">Previsualización no disponible</p>
        </div>`;

            } else if (file.type.startsWith('text/')) {
                const reader = new FileReader();
                reader.onload = () => {
                    preview.innerHTML = `
          <div class="border p-3 rounded bg-white text-start" style="max-height: 150px; overflow-y: auto;">
            <p class="small fw-bold">${file.name}</p>
            <pre class="small mb-0">${reader.result.substring(0, 200)}...</pre>
          </div>`;
                };
                reader.readAsText(file);

            } else {
                preview.innerHTML = `
        <div class="border p-3 text-center rounded bg-white">
          <i class="bi bi-file-earmark-fill fs-1 text-secondary"></i>
          <p class="small mt-2">${file.name}</p>
          <p class="small text-muted">Previsualización no disponible</p>
        </div>`;
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