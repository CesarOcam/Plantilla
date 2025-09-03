<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /../../login.php');  // Ruta desde la raíz del servidor web
    exit;
}

include_once(__DIR__ . '/../conexion.php'); // Ajusta el path según sea necesario

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
$id2 = isset($_GET['id']) ? (int) $_GET['id'] : 1; //PDF

$stmt = $con->prepare("
    SELECT 
        r.AduanaId,
        a.nombre_corto_aduana AS nombre_aduana,
        r.Numero,
        r.ClienteExportadorId,
        exp.razonSocial_exportador AS nombre_exportador,
        r.ClienteLogisticoId,
        log.razonSocial_exportador AS nombre_logistico,
        r.Mercancia,
        r.Marcas,
        r.Pedimentos,
        r.ClavePedimento,
        r.PesoBruto,
        r.Cantidad,
        r.Bultos,
        r.Contenedor,
        r.ConsolidadoraId,
        cons.denominacion_consolidadora AS nombre_consolidadora,
        r.ResultadoModulacion,
        CASE 
            WHEN r.Status = 1 THEN 'EN TRÁFICO'
            WHEN r.Status = 2 THEN 'EN CONTABILIDAD'
            WHEN r.Status = 3 THEN 'FACTURADA'
            ELSE 'INACTIVO'
        END AS Status_texto,
        CASE 
            WHEN r.ResultadoModulacion = 1 THEN 'VERDE'
            WHEN r.ResultadoModulacion = 0 THEN 'ROJO'
            ELSE ''
        END AS ResultadoModulacion_texto,
        r.RecintoId,
        rec.inmueble_recintos AS inmueble_recintos,
        r.NavieraId,
        nav.nombre_transportista AS nombre_naviera,
        r.CierreDocumentos,
        r.FechaPago,
        r.BuqueId,
        bq.identificacion AS nombre_buque,
        r.Booking,
        r.CierreDespacho,
        r.HoraDespacho,
        r.Viaje,
        r.SuReferencia,
        r.CierreDocumentado,
        r.LlegadaEstimada,
        r.PuertoDescarga,
        r.PuertoDestino,
        r.Comentarios,
        r.FechaAlta,
        r.Status,
        r.UsuarioAlta,
        u.name AS nombre_usuario_alta
    FROM conta_referencias r
    LEFT JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
    LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
    LEFT JOIN 01clientes_exportadores log ON r.ClienteLogisticoId = log.id01clientes_exportadores
    LEFT JOIN consolidadoras cons ON r.ConsolidadoraId = cons.id_consolidadora
    LEFT JOIN 2221_recintos rec ON r.RecintoId = rec.id2221_recintos
    LEFT JOIN transportista nav ON r.NavieraId = nav.idtransportista
    LEFT JOIN transporte bq ON r.BuqueId = bq.idtransporte
    LEFT JOIN sec_users u ON r.UsuarioAlta = u.login
    WHERE r.Id = :id
");


$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$referencia = $stmt->fetch(PDO::FETCH_ASSOC);

$aduanaId = $referencia['AduanaId'];
$recintoSeleccionado = $referencia['RecintoId'];

// ADUANAS
$stmt = $con->prepare("SELECT id2201aduanas, nombre_corto_aduana 
                       FROM 2201aduanas 
                       WHERE nombre_corto_aduana IS NOT NULL 
                         AND TRIM(nombre_corto_aduana) != ''
                         AND id2201aduanas NOT IN (1, 63, 72, 104)
                       ORDER BY nombre_corto_aduana");
$stmt->execute();
$aduana = $stmt->fetchAll(PDO::FETCH_ASSOC);

// EXPORTADORES Y LOGISTICOS
$stmt = $con->prepare("SELECT id01clientes_exportadores, razonSocial_exportador
                       FROM 01clientes_exportadores 
                       ORDER BY razonSocial_exportador");
$stmt->execute();
$exp = $stmt->fetchAll(PDO::FETCH_ASSOC);

// RECINTOS
$recintos = [];

if (!empty($aduanaId)) {
    // Primero obtenemos el nombre_corto_aduana de 2201aduanas
    $stmtNombre = $con->prepare("SELECT nombre_corto_aduana FROM 2201aduanas WHERE id2201aduanas = ?");
    $stmtNombre->execute([$aduanaId]);
    $nombreCorto = $stmtNombre->fetchColumn();

    if ($nombreCorto) {
        $nombreCorto = strtoupper(trim($nombreCorto)); // Aseguramos consistencia

        // Reemplazo especial si es CDMX
        if ($nombreCorto === 'CDMX') {
            $nombreCorto = 'AEROPUERTO INTERNACIONAL DE LA CIUDAD DE MÉXICO';
        }

        // Ahora buscamos los recintos donde aduanaFiscalizada coincida con nombre_corto_aduana
        $stmt = $con->prepare("
            SELECT r.id2206_recintos_fiscalizados, r.recintoFiscalizado, r.nombre_conocido_recinto
            FROM 2206_recintos_fiscalizados r
            INNER JOIN (
                SELECT MIN(id2206_recintos_fiscalizados) AS id_min
                FROM 2206_recintos_fiscalizados
                WHERE UPPER(aduanaFiscalizada) = :nombre
                AND nombre_conocido_recinto IS NOT NULL
                AND nombre_conocido_recinto != ''
                GROUP BY nombre_conocido_recinto
            ) sub ON r.id2206_recintos_fiscalizados = sub.id_min
            ORDER BY r.nombre_conocido_recinto
        ");

        $stmt->execute(['nombre' => $nombreCorto]);
        $recintos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


$stmt = $con->prepare("SELECT idtransportista, nombre_transportista
                       FROM transportista 
                       WHERE nombre_transportista IS NOT NULL AND nombre_transportista != ''
                       ORDER BY nombre_transportista");
$stmt->execute();
$navieras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $con->prepare("SELECT idtransporte, identificacion
                       FROM transporte 
                       WHERE identificacion IS NOT NULL AND identificacion != ''
                       ORDER BY identificacion");
$stmt->execute();
$buques = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCons = $con->prepare("SELECT id_consolidadora, denominacion_consolidadora 
                        FROM consolidadoras 
                        ORDER BY denominacion_consolidadora");
$stmtCons->execute();
$consolidadoras = $stmtCons->fetchAll(PDO::FETCH_ASSOC);

$stmtContenedor = $con->prepare("SELECT idcontenedor, codigo, tipo, sello 
                                 FROM conta_contenedores 
                                 WHERE referencia_id = :id
                                 ORDER BY idcontenedor ASC");
$stmtContenedor->execute(['id' => $id]);
$contenedores = $stmtContenedor->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Información Referencia</title>
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

<body>
    <div class="container-fluid">
        <div class="card mt-3 border shadow rounded-0">
            <form id="form_Referencia" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="ReferenciaId" name="id" value="<?php echo $id; ?>">
                <div class="card-header formulario_referencia">
                    <h5 class="mt-3">Información Referencia - <?php echo $referencia['Numero']; ?></h5>

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
                                <div class="col-2 col-sm-1 d-flex flex-column mt-4">
                                    <label for="referencia" class="form-label text-muted small">REFERENCIA:</label>
                                    <input id="referencia" name="referencia" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0 disabled-input"
                                        value="<?= htmlspecialchars($referencia['Numero']); ?>" readonly>
                                </div>
                                <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                                    <label for="aduana" class="form-label text-muted small">ADUANA:</label>
                                    <input id="aduana" name="aduana" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0 disabled-input"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['nombre_aduana']; ?>" readonly>
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="exportador" class="form-label text-muted small">EXPORTADOR:</label>
                                    <select id="exportador-select" name="exportador"
                                        class="form-control rounded-0 border-0 border-bottom text-muted">
                                        <option value="" disabled <?= empty($referencia['ClienteExportadorId']) ? 'selected' : '' ?>>
                                            Exportador *
                                        </option>
                                        <?php foreach ($exp as $item): ?>
                                            <option value="<?= $item['id01clientes_exportadores'] ?>"
                                                <?= $item['id01clientes_exportadores'] == $referencia['ClienteExportadorId'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['razonSocial_exportador']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="logistico" class="form-label text-muted small">LOGÍSTICO:</label>
                                    <select id="logistico-select" name="logistico"
                                        class="form-control rounded-0 border-0 border-bottom text-muted">
                                        <option value="" disabled <?= empty($referencia['ClienteLogisticoId']) ? 'selected' : '' ?>>
                                            Exportador *
                                        </option>
                                        <?php foreach ($exp as $item): ?>
                                            <option value="<?= $item['id01clientes_exportadores'] ?>"
                                                <?= $item['id01clientes_exportadores'] == $referencia['ClienteLogisticoId'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['razonSocial_exportador']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="mercancia" class="form-label text-muted small">IDENTIFICACIÓN DE LA
                                        MERCANCÍA EXPORTADA:</label>
                                    <input id="mercancia" name="mercancia" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Mercancia']; ?>">
                                </div>
                                <!-- FILA 2 -->
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="marcas" class="form-label text-muted small">MARCAS:</label>
                                    <input id="marcas" name="marcas" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Marcas']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="pedimento" class="form-label text-muted small">PEDIMENTO:</label>
                                    <input id="pedimento" name="pedimento" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Pedimentos']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="clave" class="form-label text-muted small">CLAVE PEDIMENTO:</label>
                                    <select id="clave-select" name="clave"
                                        class="form-control rounded-0 border-0 border-bottom text-muted">
                                        <option value="" disabled <?= empty($referencia['ClavePedimento']) ? 'selected' : '' ?>>
                                            Seleccione Clave *</option>
                                        <?php foreach ($clavePedimento as $item): ?>
                                            <option value="<?= htmlspecialchars($item['id2202clave_pedimento']) ?>"
                                                <?= ($item['id2202clave_pedimento'] == $referencia['ClavePedimento']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['claveCve']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                                    <label for="peso" class="form-label text-muted small">PESO BRUTO:</label>
                                    <input id="peso" name="peso" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['PesoBruto']; ?>">
                                </div>

                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="bultos" class="form-label text-muted small">CANTIDAD Y BULTOS:</label>
                                    <input id="bultos" name="bultos" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Cantidad']; ?>">
                                </div>

                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="consolidadora"
                                        class="form-label text-muted small">CONSOLIDADORA:</label>
                                    <select id="consolidadora-select" name="consolidadora"
                                        class="form-control rounded-0 border-0 border-bottom text-muted">
                                        <option value="" disabled <?= empty($referencia['ConsolidadoraId']) ? 'selected' : '' ?>>
                                            Consolidadora *
                                        </option>
                                        <?php foreach ($consolidadoras as $cons): ?>
                                            <option value="<?= $cons['id_consolidadora'] ?>"
                                                <?= $cons['id_consolidadora'] == $referencia['ConsolidadoraId'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cons['denominacion_consolidadora']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="modulacion" class="form-label text-muted small">RESULTADO
                                        MODULACIÓN:</label>
                                    <select id="modulacion" name="modulacion"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent; cursor: pointer;">
                                        <option value="" <?php echo is_null($referencia['ResultadoModulacion']) ? 'selected' : ''; ?>></option>
                                        <option value="1" <?php echo ($referencia['ResultadoModulacion'] === '1' || $referencia['ResultadoModulacion'] === 1) ? 'selected' : ''; ?>>VERDE</option>
                                        <option value="0" <?php echo ($referencia['ResultadoModulacion'] === '0' || $referencia['ResultadoModulacion'] === 0) ? 'selected' : ''; ?>>ROJO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="recinto" class="form-label text-muted small">RECINTO:</label>
                                    <select id="recinto-select" name="recinto"
                                        class="form-control rounded-0 border-0 border-bottom text-muted"
                                        <?= empty($aduanaId) ? 'disabled' : '' ?>>

                                        <?php if (empty($aduanaId)): ?>
                                            <option value="" selected disabled>Seleccione una aduana primero</option>
                                        <?php else: ?>
                                            <option value="" disabled <?= empty($recintoSeleccionado) ? 'selected' : '' ?>>--
                                                Selecciona un recinto --</option>
                                            <?php foreach ($recintos as $rec): ?>
                                                <option value="<?= $rec['id2206_recintos_fiscalizados'] ?>"
                                                    <?= $rec['id2206_recintos_fiscalizados'] == $recintoSeleccionado ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($rec['nombre_conocido_recinto']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="naviera" class="form-label text-muted small">NAVIERA:</label>
                                    <select id="naviera-select" name="naviera"
                                        class="form-control rounded-0 border-0 border-bottom text-muted">
                                        <option value="" disabled <?= empty($referencia['NavieraId']) ? 'selected' : '' ?>>
                                            Naviera *
                                        </option>
                                        <?php foreach ($navieras as $item): ?>
                                            <option value="<?= $item['idtransportista'] ?>"
                                                <?= $item['idtransportista'] == $referencia['NavieraId'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['nombre_transportista']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="cierre_doc" class="form-label text-muted small">CIERRE DE
                                        DOCUMENTOS:</label>
                                    <input id="cierre_doc" name="cierre_doc" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['CierreDocumentos']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="fecha_pago" class="form-label text-muted small">FECHA PAGO:</label>
                                    <input id="fecha_pago" name="fecha_pago" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['FechaPago']; ?>">
                                </div>
                                <!-- Tab2 Row2 -->
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="buque" class="form-label text-muted small">BUQUE:</label>
                                    <select id="buque-select" name="buque"
                                        class="form-control rounded-0 border-0 border-bottom text-muted">
                                        <option value="" disabled <?= empty($referencia['BuqueId']) ? 'selected' : '' ?>>
                                            Seleccione buque *</option>
                                        <?php foreach ($buques as $item): ?>
                                            <option value="<?= htmlspecialchars($item['idtransporte']) ?>"
                                                <?= ($item['idtransporte'] == $referencia['BuqueId']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($item['identificacion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="booking" class="form-label text-muted small">BOOKING:</label>
                                    <input id="booking" name="booking" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Booking']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="cierre_desp" class="form-label text-muted small">CIERRE
                                        DESPACHO:</label>
                                    <input id="cierre_desp" name="cierre_desp" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['CierreDespacho']; ?>">
                                </div>
                                <!-- Hora de Despacho con icono y timepicker -->
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="hora_desp" class="form-label text-muted small">HORA DESPACHO:</label>
                                    <input id="hora_desp" name="hora_desp" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['HoraDespacho']; ?>">
                                </div>

                                <!-- Tab2 Row3 -->
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="viaje" class="form-label text-muted small">VIAJE:</label>
                                    <input id="viaje" name="viaje" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Viaje']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="SuReferencia" class="form-label text-muted small">REFERENCIA
                                        EXTERNA:</label>
                                    <input id="SuReferencia" name="SuReferencia" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['SuReferencia']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="fecha_doc" class="form-label text-muted small">FECHA DE
                                        DOCUMENTADO:</label>
                                    <input id="fecha_doc" name="fecha_doc" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['CierreDocumentado']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="fecha_eta" class="form-label text-muted small">FECHA ESTIMADA DE LLEGADA
                                        (ETA):</label>
                                    <input id="fecha_eta" name="fecha_eta" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['LlegadaEstimada']; ?>">
                                </div>
                                <!-- Tab2 Row3 -->
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="puerto_desc" class="form-label text-muted small">PUERTO DE
                                        DESCARGA:</label>
                                    <input id="puerto_desc" name="puerto_desc" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['PuertoDescarga']; ?>">
                                </div>
                                <div class="col-2 col-sm-3 d-flex flex-column mt-4">
                                    <label for="puerto_dest" class="form-label text-muted small">PUERTO DE
                                        DESTINO:</label>
                                    <input id="puerto_dest" name="puerto_dest" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['PuertoDestino']; ?>">
                                </div>
                                <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                                    <label for="usuario_alta" class="form-label text-muted small">USUARIO ALTA:</label>
                                    <input id="usuario_alta" name="usuario_alta" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['nombre_usuario_alta']; ?>" readonly>
                                </div>
                                <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                                    <label for="fecha_alta" class="form-label text-muted small">FECHA ALTA:</label>
                                    <input id="fecha_alta" name="fecha_alta" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['FechaAlta']; ?>" readonly>
                                </div>
                                <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                                    <label for="status" class="form-label text-muted small">STATUS:</label>
                                    <input id="status" name="status" type="text"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent;"
                                        value="<?php echo $referencia['Status_texto']; ?>" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-2 col-sm-6 d-flex flex-column mt-4">
                                    <label for="comentarios" class="form-label text-muted small">COMENTARIOS:</label>
                                    <textarea id="comentarios" name="comentarios"
                                        class="form-control input-transparent border-0 border-bottom rounded-0"
                                        style="background-color: transparent; resize: none;"
                                        rows="4"><?php echo htmlspecialchars($referencia['Comentarios']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="direccion" role="tabpanel">
                            <div class="row ms-2 me-2">
                                <?php
                                include_once("tabla_movimientos.php");
                                ?>
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
                                                    <?php if (!empty($contenedores)): ?>
                                                        <?php foreach ($contenedores as $index => $c): ?>
                                                            <?php
                                                            $rowId = "fila-{$index}";
                                                            $inputId = "input-contenedor-{$index}";
                                                            $mensajeId = "mensajeContenedor-{$index}";
                                                            $iconoId = "iconoValidacion-{$index}";
                                                            ?>
                                                            <tr id="<?= $rowId ?>" class="text-center">
                                                                <td class="text-center align-middle">
                                                                    <i class="bi bi-box-fill text-success fs-4 me-2"></i>
                                                                </td>
                                                                <td>
                                                                    <div class="position-relative">
                                                                        <input type="text" id="<?= $inputId ?>"
                                                                            name="contenedor[]"
                                                                            class="form-control ps-4 rounded-0 border-0 border-bottom text-center"
                                                                            value="<?= htmlspecialchars($c['codigo']) ?>"
                                                                            maxlength="11" required>
                                                                        <i id="<?= $iconoId ?>"
                                                                            class="bi position-absolute top-50 end-0 translate-middle-y me-2"></i>
                                                                        <small id="<?= $mensajeId ?>"
                                                                            class="form-text ms-1 mt-1"></small>
                                                                        <input type="hidden" name="contenedor_id[]"
                                                                            value="<?= $c['idcontenedor'] ?>">
                                                                    </div>
                                                                </td>
                                                                <td class="text-center">
                                                                    <select name="tipo[]"
                                                                        class="form-select tipo-select form-control ps-4 rounded-0 border-0 border-bottom text-center"
                                                                        style="width: 100%;" required>
                                                                        <option value="">Seleccione un tipo</option>
                                                                        <?php foreach ($tiposContenedor as $tc): ?>
                                                                            <option
                                                                                value="<?= htmlspecialchars($tc['id2210_tipo_contenedor']) ?>"
                                                                                <?= ($tc['id2210_tipo_contenedor'] == $c['tipo']) ? 'selected' : '' ?>>
                                                                                <?= htmlspecialchars($tc['id2210_tipo_contenedor'] . ' - ' . $tc['descripcion_contenedor']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <input type="text" name="sello[]"
                                                                        class="form-control ps-4 rounded-0 border-0 border-bottom text-center"
                                                                        value="<?= htmlspecialchars($c['sello']) ?>">
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    <button type="button"
                                                                        class="btn btn-md btn-danger rounded-0">Eliminar</button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>

                                                        <!-- Aplicar validación a cada input generado -->
                                                        <script>
                                                            document.addEventListener("DOMContentLoaded", function() {
                                                                <?php foreach ($contenedores as $index => $c): ?>
                                                                    agregarValidacion("<?= "input-contenedor-{$index}" ?>", "<?= "mensajeContenedor-{$index}" ?>", "<?= "iconoValidacion-{$index}" ?>");
                                                                <?php endforeach; ?>
                                                                contador = <?= count($contenedores) ?>;
                                                            });
                                                        </script>
                                                    <?php endif; ?>
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

                        <div class="tab-pane fade" id="opciones" role="tabpanel">
                            <div class="row mt-4">
                                <div class="col-12 mt-5">
                                    <button type="button" id="btn_subirDocs" class="btn btn-outline-secondary btn-md rounded-0"
                                        data-bs-toggle="modal" data-bs-target="#modalDocumentos">
                                        <i class="bi bi-upload me-1"></i> Subir Documentos
                                    </button>

                                    <div class="table-responsive mt-3">
                                                                                        <?php
                                                include_once("tabla_archivos_referencia.php");
                                                ?>   
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row justify-content-end mt-auto">
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <a href="../../vistas/pdfs/formato-02.php?id=<?= $id2 ?>" target="_blank"
                                class="btn btn-outline-secondary d-flex align-items-center px-3 py-2 rounded-0 shadow-sm"
                                title="Ver Solicitudes">
                                <i class="fas fa-file-alt me-2"></i> Carátula
                            </a>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <a href="../../vistas/pdfs/formato-01.php?id=<?= $id2 ?>" target="_blank"
                                class="btn btn-outline-secondary d-flex align-items-center px-3 py-2 rounded-0 shadow-sm"
                                title="Ver Solicitudes">
                                <i class="fas fa-file-pdf me-2"></i> Imprimir CG
                            </a>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">

                            <?php if (isset($referencia['Status']) && $referencia['Status'] == 1): ?>
                                <button type="button" class="btn btn-outline-secondary rounded-0" id="btn_actualizar"
                                    data-id="<?= $id2 ?>">
                                    <i class="fas fa-share me-2"></i> Pasar a Contabilidad
                                </button>
                            <?php elseif (isset($referencia['Status']) && $referencia['Status'] == 2): ?>
                                <button type="button" class="btn btn-outline-secondary rounded-0" id="btn_kardex"
                                    data-id="<?= $id2 ?>">
                                    <i class="fas fa-dolly me-2"></i> Afectar Kardex
                                </button>
                            <?php elseif (isset($referencia['Status']) && $referencia['Status'] == 3): ?>
                                <!--<button type="button" class="btn btn-outline-secondary rounded-0" id="btn_correo"
                                    data-id="<?= $id2 ?>">
                                    <i class="fas fa-paper-plane me-2"></i> Enviar CG a Cliente
                                </button>-->
                                <button type="button" class="btn btn-outline-secondary rounded-0" id="btn_EnvioCG"
                                    data-id="<?= $id2 ?>" data-bs-toggle="modal" data-bs-target="#modalComplementaria">
                                    <i class="fas fa-paper-plane me-2"></i> Enviar CG al cliente
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <?php if (isset($referencia['Status']) && $referencia['Status'] == 3): ?>
                                <button type="button" class="btn btn-outline-secondary rounded-0" id="btn_complementaria"
                                    data-id="<?= $id2 ?>">
                                    Crear Complementaria
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" class="btn btn-outline-danger rounded-0"
                                onclick="window.location.href='../../vistas/consultas/consulta_referencia.php'">Salir</button>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="submit" class="btn btn-secondary rounded-0" id="btn_guardar"
                                <?= ($referencia['Status'] == 3) ? 'disabled' : '' ?>>
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>

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
                                        <i class="bi bi-cloud-arrow-up-fill"
                                            style="font-size: 4rem; color: #0d6efd;"></i>
                                        <p class="text-muted mb-0 mt-3">Arrastra los documentos aquí</p>
                                    </div>
                                    <!-- Contenedor para previsualización (oculto al principio) -->
                                    <div id="previewContainer" class="row row-cols-1 row-cols-md-3 g-3 d-none"
                                        style="overflow-y: auto; max-height: 350px; margin-top: 1rem;">
                                        <!-- Vistas previas dinámicas -->
                                    </div>
                                </div>

                                <div class="text-center mb-3">
                                    <button type="button" class="btn btn-outline-primary" id="btnBuscarArchivos">
                                        <i class="bi bi-folder-fill me-2"></i> Seleccionar desde carpeta
                                    </button>
                                    <input type="file" id="documentosInput" name="documentos[]" multiple hidden>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="button" class="btn btn-primary" id="btnAgregarDocs">Agregar a la
                                        referencia</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- MODAL ENVIO CG -->
                <div class="modal fade" id="modalEnvioCG" tabindex="-1" aria-labelledby="modalEnvioCGLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEnvioCGLabel">Enviar Cuenta de Gastos a Cliente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Aquí se cargan las tablas desde tablas_correos_cg.php -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger rounded-0" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-outline-secondary rounded-0" id="btn_enviarCG" data-referencia-id="<?= $id2 ?>">Enviar CG</button>
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
        $(document).ready(function() {
            function initSelect2(id, placeholder) {
                $(id).select2({
                    placeholder: placeholder,
                    allowClear: false,
                    width: '100%'
                });
            }

            // Inicialización de todos los select2
            initSelect2('#aduana-select', 'Aduana');
            initSelect2('#exportador-select', 'Exportador *');
            initSelect2('#logistico-select', 'Logístico *');
            initSelect2('#recinto-select', 'Recinto');
            initSelect2('#clave-select', 'Clave Pedimento');
            initSelect2('#consolidadora-select', 'Consolidadora');
            initSelect2('#naviera-select', 'Naviera');
            initSelect2('#buque-select', 'Buque');
            initSelect2('.tipo-select', 'Tipo Buque');

            // Coloca automáticamente el cursor en la búsqueda al abrir select2
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    const input = document.querySelector('.select2-container--open .select2-search__field');
                    if (input) input.focus();
                }, 100);
            });

            // BLOQUEAR y aplicar estilo si Status = 3
            const status = <?= $referencia['Status']; ?>;
            if (status == 3) {
                // Inputs individuales
                $('#mercancia').prop('readonly', true).addClass('disabled-input');
                $('#marcas').prop('readonly', true).addClass('disabled-input');
                $('#pedimento').prop('readonly', true).addClass('disabled-input');
                $('#peso').prop('readonly', true).addClass('disabled-input');
                $('#bultos').prop('readonly', true).addClass('disabled-input');
                $('#booking').prop('readonly', true).addClass('disabled-input');
                $('#viaje').prop('readonly', true).addClass('disabled-input');
                $('#SuReferencia').prop('readonly', true).addClass('disabled-input');
                $('#puerto_desc').prop('readonly', true).addClass('disabled-input');
                $('#puerto_dest').prop('readonly', true).addClass('disabled-input');
                $('#usuario_alta').prop('readonly', true).addClass('disabled-input');
                $('#fecha_alta').prop('readonly', true).addClass('disabled-input');
                $('#status').prop('readonly', true).addClass('disabled-input');
                $('#comentarios').prop('readonly', true).addClass('disabled-input');

                // Select2
                const selects = [
                    '#aduana-select', '#exportador-select', '#logistico-select',
                    '#recinto-select', '#clave-select', '#consolidadora-select',
                    '#naviera-select', '#buque-select', '.tipo-select'
                ];

                selects.forEach(selector => {
                    $(selector).prop('disabled', true) // deshabilitar
                        .addClass('disabled-input'); // agregar clase para estilo
                    $(selector).next('.select2-container').addClass('select2-disabled'); // estilo gris select2
                });

                // Opcional: input de referencia también
                $('#referencia').addClass('disabled-input');
            }
        });

        $(document).ready(function() {
            const status = <?= $referencia['Status']; ?>; // Traer status desde PHP

            if (status == 3) {
                // Bloquear todos los botones 
                $('button.btn-danger:contains("Eliminar")').prop('disabled', true).addClass('disabled');
                $('#btn-nuevo-contenedor').prop('disabled', true).addClass('disabled'); 
                $('#btn_subirDocs').prop('disabled', true).addClass('disabled');
            }
        });


        $(document).ready(function() {
            // Status de la referencia
            const status = <?= $referencia['Status']; ?>;

            // Seleccionar todos los inputs
            const inputs = ["#cierre_doc", "#cierre_desp", "#fecha_pago", "#hora_desp", "#fecha_doc", "#fecha_eta"];

            inputs.forEach(id => {
                // Configuración por defecto
                const options = {
                    dateFormat: "Y-m-d"
                };

                // Configuración específica para horaDesp
                if (id === "#hora_desp") {
                    options.enableTime = true;
                    options.noCalendar = true;
                    options.dateFormat = "H:i";
                    options.time_24hr = true;
                    options.allowInput = true;
                }

                // Si status == 3, desactivar apertura de calendario
                if (status == 3) {
                    options.clickOpens = false; // Evita que se abra el calendario
                }

                // Inicializar Flatpickr
                flatpickr(id, options);

                // Agregar clase disabled-input a todos los inputs si status == 3
                if (status == 3) {
                    $(id).addClass('disabled-input');
                }
            });

        });

        let contador = 1;

        const tablaContenedores = document.getElementById("tabla-contenedores").querySelector("tbody");

        function verificarCodificacion(contenedor) {
            const tabla = {
                'A': 10,
                'B': 12,
                'C': 13,
                'D': 14,
                'E': 15,
                'F': 16,
                'G': 17,
                'H': 18,
                'I': 19,
                'J': 20,
                'K': 21,
                'L': 23,
                'M': 24,
                'N': 25,
                'O': 26,
                'P': 27,
                'Q': 28,
                'R': 29,
                'S': 30,
                'T': 31,
                'U': 32,
                'V': 34,
                'W': 35,
                'X': 36,
                'Y': 37,
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

        document.getElementById('btn-nuevo-contenedor').addEventListener('click', function() {
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

            // Inicializa select2 solo en el nuevo select agregado
            $('.tipo-select').last().select2({
                placeholder: 'Seleccione un tipo',
                allowClear: false,
                width: '100%'
            });

            agregarValidacion(inputId, mensajeId, iconoId);

            contador++;
        });

        function agregarValidacion(inputId, mensajeId, iconoId) {
            const inputContenedor = document.getElementById(inputId);
            const mensajeContenedor = document.getElementById(mensajeId);
            const icono = document.getElementById(iconoId);

            // Busca ícono que puede ser box-fill o box-seam en la primera celda
            const fila = inputContenedor.closest("tr");
            const iconoContenedor = fila.querySelector(".bi-box-fill, .bi-box-fill");

            inputContenedor.addEventListener('input', function() {
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

        function actualizarNumeracion() {
            const filas = tablaContenedores.querySelectorAll("tr");
            filas.forEach((fila, index) => {
                const celdaNumero = fila.querySelector(".numero-fila");
                if (celdaNumero) {
                    celdaNumero.textContent = index + 1;
                }
                fila.id = `fila-${index}`;
                const input = fila.querySelector('input[name="contenedor[]"]');
                const tipo = fila.querySelector('input[name="tipo[]"]');
                const sello = fila.querySelector('input[name="sello[]"]');
                const icono = fila.querySelector('i.bi');
                const mensaje = fila.querySelector('fill.form-text');

                if (input) input.id = `input-contenedor-${index}`;
                if (icono) icono.id = `iconoValidacion-${index}`;
                if (mensaje) mensaje.id = `mensajeContenedor-${index}`;

                if (input && icono && mensaje) {
                    agregarValidacion(input.id, mensaje.id, icono.id);
                }
            });

            contador = filas.length;
        }

        tablaContenedores.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-danger')) {
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

        document.getElementById('btnAgregarDocs').addEventListener('click', function() {
            const tableBody = document.querySelector('#tabla-archivos tbody');

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

        tablaContenedores.addEventListener('click', function(e) {
            if (e.target.closest('.btn-danger')) { // o '#btn-eliminar', según tengas el selector
                const filaEliminada = e.target.closest('tr');
                // Capturar el idcontenedor de esa fila
                const inputIdContenedor = filaEliminada.querySelector('input[name="contenedor_id[]"]');
                if (inputIdContenedor) {
                    const idContenedor = inputIdContenedor.value;
                    // Crear input hidden para indicar eliminación
                    const inputEliminado = document.createElement('input');
                    inputEliminado.type = 'hidden';
                    inputEliminado.name = 'contenedores_eliminados[]';
                    inputEliminado.value = idContenedor;

                    // Agregar al formulario
                    document.querySelector('form').appendChild(inputEliminado);
                }
                // Eliminar fila visualmente
                filaEliminada.remove();

                // Si tienes función para renumerar IDs
                if (typeof actualizarNumeracion === 'function') actualizarNumeracion();
            }
        });
    </script>
    <script src="../../../js/actualizar/enviar_cg.js"></script>
    <script src="../../../js/consultar_Correos_Modal.js"></script>
    <script src="../../../js/guardar_Complementaria.js"></script>
    <script src="../../../js/actualizar/pasar_Conta.js"></script>
    <script src="../../../js/actualizar/afectar_Kardex.js"></script>
    <script src="../../../js/actualizar/envio_cg.js"></script>
    <script src="../../../js/eliminar/eliminar_archivo.js"></script>
    <script src="../../../js/actualizar/actualizar_Referencias.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
    </script>
</body>

</html>