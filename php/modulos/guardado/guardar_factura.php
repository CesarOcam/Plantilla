<?php
session_start();
include('../conexion.php'); // Ajusta ruta según tu estructura
header('Content-Type: application/json');

// Validar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit;
}

$usuarioAlta = $_SESSION['usuario_id'];

// Verificar que llegan datos JSON
if (!isset($_POST['datos'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos JSON.']);
    exit;
}

$data = json_decode($_POST['datos'], true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos.']);
    exit;
}

// Verificar que haya archivos
if (empty($_FILES)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron archivos.']);
    exit;
}

// Para debug: muestra qué archivos llegan al servidor
error_log("Archivos recibidos: " . print_r($_FILES, true));

$errores = [];
$insertados = 0;

// Carpeta base para guardar archivos
$uploadDirBase = '../../../docs/';

foreach ($data as $index => $factura) {
    // Datos básicos
    $serie = $factura['serie'] ?? '';
    $folio = $factura['folio'] ?? '';
    $serieFolio = $serie . $folio;
    $rfc_proveedor = $factura['rfcProveedor'] ?? '';
    $proveedor = $factura['proveedor'] ?? '';
    $rfc_cliente = $factura['rfcCliente'] ?? '';
    $cliente = $factura['cliente'] ?? '';
    $fecha = $factura['fecha'] ?? '';
    $importe = $factura['importe'] ?? 0;
    $uuid = $factura['uuid'] ?? '';

    $idReferencia = $factura['referencia_id'] ?? 'integradas_sin_referencia';

    $uploadDir = $uploadDirBase . $idReferencia . '/';

    // Crear carpeta si no existe
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $errores[] = "No se pudo crear carpeta para referencia $idReferencia";
            continue; // saltar esta factura
        }
    }

    // Mover archivos XML y PDF
    $destinoXml = null;
    $destinoPdf = null;

    // XML
    if (isset($_FILES["xml_$index"]) && $_FILES["xml_$index"]['error'] === UPLOAD_ERR_OK) {
        $tmpXml = $_FILES["xml_$index"]['tmp_name'];
        $nombreXml = basename($_FILES["xml_$index"]['name']);
        $destinoXml = $uploadDir . $nombreXml;

        if (!move_uploaded_file($tmpXml, $destinoXml)) {
            $errores[] = "Error al mover archivo XML $nombreXml índice $index";
            error_log("Error move_uploaded_file XML índice $index: tmp='$tmpXml', destino='$destinoXml'");
            error_log("Archivo tmp existe? " . (file_exists($tmpXml) ? "Sí" : "No"));
            error_log("Permisos carpeta destino: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
            continue;
        }
    }

    // PDF
    if (isset($_FILES["pdf_$index"]) && $_FILES["pdf_$index"]['error'] === UPLOAD_ERR_OK) {
        $tmpPdf = $_FILES["pdf_$index"]['tmp_name'];
        $nombrePdf = basename($_FILES["pdf_$index"]['name']);
        $destinoPdf = $uploadDir . $nombrePdf;

        if (!move_uploaded_file($tmpPdf, $destinoPdf)) {
            $errores[] = "Error al mover archivo PDF $nombrePdf índice $index";
            error_log("Error move_uploaded_file PDF índice $index: tmp='$tmpPdf', destino='$destinoPdf'");
            continue;
        }
    }

    // Validación básica: datos obligatorios
    if (!$folio || !$rfc_proveedor || !$rfc_cliente || !$proveedor || !$cliente || !$fecha) {
        $errores[] = "Archivo #$index con datos incompletos.";
        continue;
    }

    // Verificar UUID duplicado en base de datos y obtener Número de referencia
    $stmtDup = $con->prepare("
    SELECT f.id, f.folio, f.referencia_id, r.Numero 
    FROM conta_facturas_registradas f
    LEFT JOIN conta_referencias r ON f.referencia_id = r.Id
    WHERE f.uuid = ?
");
    $stmtDup->execute([$uuid]);
    $duplicado = $stmtDup->fetch(PDO::FETCH_ASSOC);

    if ($duplicado) {
        // Si Numero es null, usamos folio
        $referenciaOfolio = $duplicado['Numero'] ?? $duplicado['folio'];
        $tipo = $duplicado['Numero'] ? 'referencia' : 'folio';

        echo json_encode([
            'success' => false,
            'duplicado' => true,
            'uuid' => $uuid,
            'referencia_numero' => $referenciaOfolio, // para JS
            'tipo' => $tipo, // 'referencia' o 'folio'
            'mensaje' => "El UUID $uuid ya existe en la base de datos, asociado a $tipo $referenciaOfolio."
        ]);
        exit;
    }


    // Insertar factura
    $sql = "INSERT INTO conta_facturas_registradas 
            (folio, rfc_proveedor, proveedor, rfc_cliente, cliente, fecha, importe, uuid, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $con->prepare($sql);
        $ok = $stmt->execute([
            $serieFolio,
            $rfc_proveedor,
            $proveedor,
            $rfc_cliente,
            $cliente,
            $fecha,
            $importe,
            $uuid,
            $usuarioAlta
        ]);

        if ($ok) {
            $insertados++;
            $facturaId = $con->lastInsertId();

            // Insertar archivos relacionados
            $archivos = [];
            if ($destinoXml)
                $archivos[] = ['nombre' => $nombreXml, 'ruta' => $destinoXml];
            if ($destinoPdf)
                $archivos[] = ['nombre' => $nombrePdf, 'ruta' => $destinoPdf];

            foreach ($archivos as $archivo) {
                $sqlArchivo = "INSERT INTO conta_referencias_archivos (Nombre, Ruta, Solicitud_factura_id, Origen) VALUES (?, ?, ?, 0)";
                $stmtArchivo = $con->prepare($sqlArchivo);
                $stmtArchivo->execute([
                    $archivo['nombre'],
                    $archivo['ruta'],
                    $facturaId
                ]);
            }
        } else {
            $errores[] = "Error al insertar factura #$index";
        }
    } catch (PDOException $e) {
        $errores[] = "Error en factura #$index: " . $e->getMessage();
    }
}

// Respuesta final
if (empty($errores)) {
    echo json_encode([
        'success' => true,
        'mensaje' => "Se procesaron correctamente $insertados archivo(s)."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => "Se procesaron $insertados archivo(s), con errores.",
        'errores' => $errores
    ]);
}
