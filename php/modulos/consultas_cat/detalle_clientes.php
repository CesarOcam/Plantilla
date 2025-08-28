<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once(__DIR__ . '/../conexion.php'); // Ajusta el path según sea necesario

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmt = $con->prepare("
    SELECT 
        ce.razonSocial_exportador, ce.curp_exportador, ce.rfc_exportador, ce.tipoClienteExportador, ce.tipo_cliente,
        ce.nombreCorto_exportador, ce.calle_exportador, ce.noExt_exportador, ce.noInt_exportador, ce.codigoPostal_exportador,
        ce.colonia_exportador, ce.localidad_exportador, ce.municipio_exportador,
        ce.idcat11_estado, est.estado, ce.id2204clave_pais, pais.pais_clave,
        ce.contacto_cliente, ce.telefono_cliente, ce.pagaCon_cliente,
        ce.status_exportador, ce.fechaAlta_exportador, ce.usuarioAlta_exportador, ce.usuarioModificar_exportador, ce.nombre_factura, ce.rfc_factura, ce.fecha_ultimaActualizacionClientes,
        logi.razonSocial_exportador AS razonSocial_logistico,
        CONCAT_WS(' ', u.NombreUsuario, u.apePatUsuario, u.apeMatUsuario) AS nombre_usuario_alta,
        CONCAT_WS(' ', um.NombreUsuario, um.apePatUsuario, um.apeMatUsuario) AS nombre_usuario_modifica
    FROM 01clientes_exportadores ce
    LEFT JOIN cat11_estados est ON ce.idcat11_estado = est.idcat11_estado
    LEFT JOIN 2204claves_paises pais ON ce.id2204clave_pais = pais.id2204clave_pais
    LEFT JOIN 01clientes_exportadores logi ON ce.logistico_asociado = logi.id01clientes_exportadores
    LEFT JOIN usuarios u ON ce.usuarioAlta_exportador = u.idusuarios
    LEFT JOIN usuarios um ON ce.usuarioModificar_exportador = um.idusuarios
    WHERE ce.id01clientes_exportadores = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Consulta para obtener los EMAILS
$stmt = $con->prepare("SELECT correo 
                        FROM `correos_01clientes_exportadores` 
                        WHERE tipo_correo = 3 AND id01clientes_exportadores = :id
                        ORDER BY correo");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obtener los países
$stmt = $con->prepare("SELECT id2204clave_pais, CONCAT(clave_SAAI_M3, ' - ', pais_clave) AS nombre_pais 
                       FROM 2204claves_paises 
                       ORDER BY clave_SAAI_M3, pais_clave");
$stmt->execute();
$paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
$paisActual = $cliente['id2204clave_pais'] ?? '';

// Consulta para obtener los estados
$stmt = $con->prepare("SELECT idcat11_estado, CONCAT(cveEdos, ' - ', estado) AS nombre_estado 
                       FROM cat11_estados 
                       ORDER BY cveEdos, estado");
$stmt->execute();
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$estadoActual = $cliente['idcat11_estado'] ?? '';

// Consulta para obtener los logísticos
$stmt = $con->prepare("SELECT id01clientes_exportadores, razonSocial_exportador 
                        FROM `01clientes_exportadores` 
                        WHERE tipo_cliente = 1
                        ORDER BY razonSocial_exportador");
$stmt->execute();
$logisticos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$logisticoActual = $cliente['razonSocial_logistico'] ?? '';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
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

    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">

</head>

<body>
    <?php
    include_once __DIR__ . '/../../../config.php';

    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>
    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <form id="form_Clientes" method="POST">
                <div class="card-header formulario_clientes">
                    <h5> Información de Cliente</h5>
                    <div class="row">
                        <div class="col col-sm-4">
                            <div class="row">
                                <input type="hidden" name="id_cliente" value="<?php echo $id; ?>">

                                <div class="col-10 col-sm-10 mt-4">
                                    <label for="nombre" class="form-label text-muted small">RAZÓN SOCIAL:</label>
                                    <input id="nombre" name="nombre" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['razonSocial_exportador']; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-10 col-sm-5 mt-4">
                                    <label for="tipo" class="form-label text-muted small">PERSONA:</label>
                                    <select id="tipo" name="tipo"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;">
                                        <option value="">Seleccione</option>
                                        <option value="1" <?php echo ($cliente['tipoClienteExportador'] == 1) ? 'selected' : ''; ?>>FÍSICA</option>
                                        <option value="2" <?php echo ($cliente['tipoClienteExportador'] == 2) ? 'selected' : ''; ?>>MORAL</option>
                                    </select>
                                </div>

                                <div class="col-10 col-sm-5 mt-4">
                                    <label for="tipo_cliente" class="form-label text-muted small">TIPO:</label>
                                    <select id="tipo_cliente" name="tipo_cliente"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;">
                                        <option value="" disabled <?php echo (!isset($cliente['tipo']) || $cliente['tipo'] == '') ? 'selected' : ''; ?>>Tipo*</option>
                                        <option value="1" <?php echo ($cliente['tipoClienteExportador'] == 1) ? 'selected' : ''; ?>>EXPORTADOR</option>
                                        <option value="2" <?php echo ($cliente['tipoClienteExportador'] == 2) ? 'selected' : ''; ?>>LOGÍSTICO</option>
                                        <option value="3" <?php echo ($cliente['tipoClienteExportador'] == 3) ? 'selected' : ''; ?>>EXPORTADOR Y LOGÍSTICO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-10 col-sm-10 mt-4">
                                    <label for="nombre_corto" class="form-label text-muted small">NOMBRE CONOCIDO:</label>
                                    <input id="nombre_corto" name="nombre_corto" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['nombreCorto_exportador']; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-10 col-sm-5 mt-4">
                                    <label for="contacto_cliente" class="form-label text-muted small">CONTACTO:</label>
                                    <input id="contacto_cliente" name="contacto_cliente" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['contacto_cliente']; ?>">
                                </div>
                                <div class="col-10 col-sm-5 mt-4">
                                    <label for="telefono_cliente" class="form-label text-muted small">TELÉFONO:</label>
                                    <input id="telefono_cliente" name="telefono_cliente" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['telefono_cliente']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col col-sm-8">
                            <div class="row">
                                <div class="col-10 col-sm-6 mt-4">
                                    <label for="calle" class="form-label text-muted small">CALLE:</label>
                                    <input id="calle" name="calle" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['calle_exportador']; ?>">
                                </div>
                                <div class="col-10 col-sm-3 mt-4">
                                    <label for="num_exterior" class="form-label text-muted small">NO. EXTERIOR:</label>
                                    <input id="num_exterior" name="num_exterior" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['noExt_exportador']; ?>">
                                </div>
                                <div class="col-10 col-sm-3 mt-4">
                                    <label for="num_interior" class="form-label text-muted small">NO. INTERIOR:</label>
                                    <input id="num_interior" name="num_interior" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['noInt_exportador']; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-10 col-sm-6 mt-4">
                                    <label for="colonia" class="form-label text-muted small">COLONIA:</label>
                                    <input id="colonia" name="colonia" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['colonia_exportador']; ?>">
                                </div>
                                <div class="col-10 col-sm-3 mt-4">
                                    <label for="localidad" class="form-label text-muted small">LOCALIDAD:</label>
                                    <input id="localidad" name="localidad" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['localidad_exportador']; ?>">
                                </div>
                                <div class="col-10 col-sm-3 mt-4">
                                    <label for="cp" class="form-label text-muted small">CP:</label>
                                    <input id="cp" name="cp" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['codigoPostal_exportador']; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-10 col-sm-4 mt-4">
                                    <label for="municipio" class="form-label text-muted small">MUNICIPIO:</label>
                                    <input id="municipio" name="municipio" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['municipio_exportador']; ?>">
                                </div>
                                <div class="col-10 col-sm-4 mt-4">
                                    <label for="pais-select" class="form-label text-muted small">PAÍS:</label>
                                    <select id="pais-select" name="pais" class="form-control rounded-0 select2"
                                        style="background-color: transparent;" aria-label="Filtrar por país" aria-describedby="basic-addon1">
                                        <option value="" disabled <?php echo empty($cliente['id2204clave_pais']) ? 'selected' : ''; ?>>País</option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id2204clave_pais']; ?>"
                                                <?php echo ($cliente['id2204clave_pais'] == $pais['id2204clave_pais']) ? 'selected' : ''; ?>>
                                                <?php echo $pais['nombre_pais']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-10 col-sm-4 mt-4">
                                    <label for="estado-select" class="form-label text-muted small">ESTADO:</label>
                                    <select id="estado-select" name="estado"
                                        class="form-control rounded-0 select2"
                                        style="background-color: transparent;">
                                        <option value="" disabled <?php echo empty($cliente['idcat11_estado']) ? 'selected' : ''; ?>>Selecciona un estado</option>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?php echo $estado['idcat11_estado']; ?>"
                                                <?php echo ($cliente['idcat11_estado'] == $estado['idcat11_estado']) ? 'selected' : ''; ?>>
                                                <?php echo $estado['nombre_estado']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-10 col-sm-3 mt-4">
                                    <label for="curp" class="form-label text-muted small">CURP:</label>
                                    <input id="curp" name="curp" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['curp_exportador']; ?>">
                                </div>
                                <div class="col-10 col-sm-3 mt-4">
                                    <label for="rfc" class="form-label text-muted small">RFC:</label>
                                    <input id="rfc" name="rfc" type="text"
                                        class="form-control rounded-0"
                                        style="background-color: transparent;" value="<?php echo $cliente['rfc_exportador']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <label for="logistico-select" class="form-label text-muted small mt-4 mb-0" style="font-weight: bold;">CLIENTES REGISTRADOS PARA FACTURA:</label>
                        <div class="col-10 col-sm-3 mt-1">
                            <label for="nombre_factura" class="form-label text-muted small">NOMBRE:</label>
                            <input id="nombre_factura" name="nombre_factura" type="text"
                                class="form-control rounded-0"
                                style="background-color: transparent;" value="<?php echo $cliente['nombre_factura']; ?>">
                        </div>
                        <div class="col-10 col-sm-9 mt-1">
                            <label for="rfc_factura_input" class="form-label text-muted small">RFC:</label>
                            <div id="rfc-container" class="form-control rounded-0 d-flex flex-wrap gap-0" style="min-height: 38px; display: flex; flex-wrap: wrap; align-items: center; background-color: transparent;">
                                <input id="rfc_factura_input" type="text" style="border: none; outline: none; flex: 1;">
                            </div>
                            <input type="hidden" id="rfc_factura_hidden" name="rfc_factura" value="<?= htmlspecialchars($cliente['rfc_factura'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-10 col-sm-3 mt-4">
                            <label for="pagaCon_cliente" class="form-label text-muted small" style="font-weight: bold;">PAGA CON:</label>
                            <select id="pagaCon_cliente" name="pagaCon_cliente"
                                class="form-control rounded-0"
                                style="background-color: transparent; cursor: pointer;">
                                <option value="" disabled <?php echo empty($cliente['pagaCon_cliente']) ? 'selected' : ''; ?>>Selecciona opción</option>
                                <option value="1" <?php echo ($cliente['pagaCon_cliente'] == 1) ? 'selected' : ''; ?>>CUENTA CLIENTE</option>
                                <option value="2" <?php echo ($cliente['pagaCon_cliente'] == 2) ? 'selected' : ''; ?>>CUENTA AMEXPORT</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-10 col-sm-3 mt-4">
                            <label for="logistico-select" class="form-label text-muted small">LOGÍSTICO ASOCIADO:</label>
                            <select id="logistico-select" name="logistico_asociado"
                                class="form-control rounded-0 select2"
                                style="background-color: transparent;">
                                <option value="" disabled <?php echo empty($logisticoActual) ? 'selected' : ''; ?>>Selecciona un logístico</option>
                                <?php foreach ($logisticos as $logistico): ?>
                                    <option value="<?php echo $logistico['id01clientes_exportadores']; ?>"
                                        <?php echo ($logistico['razonSocial_exportador'] == $logisticoActual) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($logistico['razonSocial_exportador']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-10 col-sm-9 mt-4">
                            <label for="emails_contabilidad" class="form-label text-muted small">EMAIL LOGÍSTICO:</label>
                            <div id="emails-container" class="form-control rounded-0 d-flex flex-wrap gap-1"
                                style="background-color: transparent; border-bottom: 2px solid #efefef !important;">
                                <!-- Etiquetas de correo aquí -->
                                <input type="text" id="emails_input" class="border-0 flex-grow-1" placeholder="Agregar email"
                                    style="background: transparent; outline: none; min-width: 120px;">
                            </div>
                            <!-- Campo oculto donde se guardarán los correos separados por coma -->
                            <input type="hidden" name="emails_contabilidad" id="emails_contabilidad_hidden">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-10 col-sm-3 mt-4">
                            <label for="status_exportador" class="form-label text-muted small">ESTADO:</label>
                            <select id="status_exportador" name="status_exportador"
                                class="form-control rounded-0 border-0 text-muted"
                                style="background-color: transparent; cursor: pointer;">
                                <option value="1" <?php echo ($cliente['status_exportador'] == 1) ? 'selected' : ''; ?>>ACTIVO</option>
                                <option value="0" <?php echo ($cliente['status_exportador'] == 0) ? 'selected' : ''; ?>>INACTIVO</option>
                            </select>
                        </div>
                        <div class="col-10 col-sm-3 mt-4">
                            <label for="nombre_usuario_alta" class="form-label text-muted small">USUARIO ALTA:</label>
                            <input id="nombre_usuario_alta" name="nombre_usuario_alta" type="text"
                                class="form-control input-transparent border-0 border-bottom rounded-0"
                                style="background-color: transparent;"
                                value="<?php echo $cliente['nombre_usuario_alta']; ?>" readonly>
                        </div>
                        <div class="col-10 col-sm-3 mt-4">
                            <label for="fechaAlta_exportador" class="form-label text-muted small">FECHA ALTA:</label>
                            <input id="fechaAlta_exportador" name="fechaAlta_exportador" type="text"
                                class="form-control input-transparent border-0 border-bottom rounded-0"
                                style="background-color: transparent;"
                                value="<?php echo $cliente['fechaAlta_exportador']; ?>" readonly>
                        </div>
                    </div>
                    <div class="row justify-content-end mt-5">
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" class="btn btn-outline-danger rounded-0" onclick="window.location.href='../../vistas/catalogos/cat_Clientes.php'">Salir</button>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" id="btn_editar" class="btn btn-secondary rounded-0">Modificar</button>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="submit" class="btn btn-success rounded-0" id="btn_guardar" style="display:none;">Guardar</button>
                        </div>
                    </div>
                    <div class="row mt-3"></div>
                </div>
        </div>
        </form>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <!-- Incluye jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluye Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="../../../js/actualizar/actualizar_Clientes.js"></script>

    <script>
        $(document).ready(function() {
            $('#pais-select').select2({
                placeholder: "Selecciona un país",
                allowClear: false,
                width: '100%'
            });
        });
        $(document).ready(function() {
            $('#estado-select').select2({
                placeholder: "Selecciona un estado",
                allowClear: false,
                width: '100%'
            });
        });
        $(document).ready(function() {
            $('#logistico-select').select2({
                placeholder: "Selecciona un logístico",
                allowClear: false,
                width: '100%'
            });
        });
        $(document).on('select2:open', () => {
            setTimeout(() => {
                const input = document.querySelector('.select2-container--open .select2-search__field');
                if (input) input.focus();
            }, 100);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('rfc_factura_input');
            const container = document.getElementById('rfc-container');
            const hiddenInput = document.getElementById('rfc_factura_hidden');

            // ← Cargar RFCs existentes al iniciar
            const inicial = <?= json_encode(array_filter(array_map('trim', explode(',', $cliente['rfc_factura'] ?? '')))) ?>;
            if (inicial.length > 0) {
                inicial.forEach(rfc => addTag(rfc));
                updateHiddenField();
            }

            // Al presionar Enter o coma
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const valor = input.value.trim().toUpperCase();
                    if (valor && validarRFC(valor)) {
                        addTag(valor);
                        input.value = '';
                        updateHiddenField();
                    } else if (valor) {
                        alert("RFC no válido: " + valor);
                    }
                }
            });

            // Agregar una etiqueta de RFC
            function addTag(rfc) {
                const existentes = Array.from(container.querySelectorAll('.rfc-tag span:first-child')).map(el => el.textContent);
                if (existentes.includes(rfc)) return;

                const tag = document.createElement('span');
                tag.className = 'rfc-tag';
                tag.style.cssText = 'background:#e2e6ea; padding:2px 6px; margin:2px; border-radius:3px; display:flex; align-items:center;';
                tag.innerHTML = `<span>${rfc}</span><span class="remove" style="cursor:pointer; margin-left:4px;">&times;</span>`;
                container.insertBefore(tag, input);

                tag.querySelector('.remove').addEventListener('click', () => {
                    tag.remove();
                    updateHiddenField();
                });
            }

            // Actualizar campo oculto con RFCs actuales
            function updateHiddenField() {
                const rfcs = Array.from(container.querySelectorAll('.rfc-tag span:first-child'))
                    .map(span => span.textContent.trim());
                hiddenInput.value = rfcs.join(',');
            }

            // Validación de RFC (genérico, acepta 12 o 13 caracteres alfanuméricos)
            function validarRFC(rfc) {
                return /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}?$/i.test(rfc);
            }
        });


        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('emails_input');
            const container = document.getElementById('emails-container');
            const hiddenInput = document.getElementById('emails_contabilidad_hidden');

            // ← Cargar correos existentes al iniciar
            const inicial = <?= json_encode(array_column($mails, 'correo')) ?>;
            if (inicial.length > 0) {
                inicial.forEach(email => addTag(email.trim()));
                updateHiddenField();
            }

            // Al presionar Enter o coma
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const valor = input.value.trim();
                    if (valor && validarEmail(valor)) {
                        addTag(valor);
                        input.value = '';
                        updateHiddenField();
                    } else if (valor) {
                        alert("Correo no válido: " + valor);
                    }
                }
            });

            // Agregar una etiqueta de correo
            function addTag(email) {
                // Evitar duplicados
                const existentes = Array.from(container.querySelectorAll('.email-tag span')).map(el => el.textContent);
                if (existentes.includes(email)) return;

                const tag = document.createElement('span');
                tag.className = 'email-tag';
                tag.innerHTML = `<span>${email}</span><span class="remove">&times;</span>`;
                container.insertBefore(tag, input);

                // Evento para eliminar correo
                tag.querySelector('.remove').addEventListener('click', () => {
                    tag.remove();
                    updateHiddenField();
                });
            }

            // Actualizar el campo oculto con los correos actuales
            function updateHiddenField() {
                const emails = Array.from(container.querySelectorAll('.email-tag span:first-child'))
                    .map(span => span.textContent.trim());
                hiddenInput.value = emails.join(',');
            }

            // Validación simple de email
            function validarEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
        });
    </script>
</body>

</html>