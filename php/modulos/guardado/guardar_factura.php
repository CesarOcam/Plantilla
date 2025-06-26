<?php
session_start();
include('../conexion.php');
header('Content-Type: application/json');

// Validar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit;
}
$usuarioAlta = $_SESSION['usuario_id'];

if (!isset($_POST['datos'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos JSON.']);
    exit;
}

$data = json_decode($_POST['datos'], true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos.']);
    exit;
}


if (empty($_FILES)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron archivos.']);
    exit;
}

$errores = [];
$insertados = 0;

foreach ($data as $index => $archivo) {
    // Datos básicos de la factura
    $serie = $archivo['serie'] ?? null;
    $folio = $archivo['folio'] ?? null;
    $serieFolio = ($serie ?? '') . ($folio ?? '');
    $rfc_proveedor = $archivo['rfcProveedor'] ?? null;
    $proveedor = $archivo['proveedor'] ?? null;
    $rfc_cliente = $archivo['rfcCliente'] ?? null;
    $cliente = $archivo['cliente'] ?? null;
    $fecha = $archivo['fecha'] ?? null;
    $importe = $archivo['importe'] ?? null;
    $uuid = $archivo['uuid'] ?? null;

    // Carpeta con ID de referencia o "sin_referencia"
    $idReferencia = $archivo['referencia_id'] ?? 'sin_referencia';
    $uploadDirBase = '../../../docs/';
    $uploadDir = $uploadDirBase . $idReferencia . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $errores[] = "No se pudo crear la carpeta para la referencia $idReferencia.";
            continue; // saltar este registro porque no hay carpeta
        }
    }

    // Mover archivos XML y PDF a carpeta específica
    $destinoXml = null;
    $destinoPdf = null;

    if (isset($_FILES["xml_$index"]) && $_FILES["xml_$index"]['error'] === UPLOAD_ERR_OK) {
        $tmpXml = $_FILES["xml_$index"]['tmp_name'];
        $nombreXml = basename($_FILES["xml_$index"]['name']);
        $destinoXml = $uploadDir . $nombreXml;
        if (!move_uploaded_file($tmpXml, $destinoXml)) {
            $errores[] = "Error al mover archivo XML $nombreXml del índice $index";
            $destinoXml = null; // No existirá archivo
        }
    }

    if (isset($_FILES["pdf_$index"]) && $_FILES["pdf_$index"]['error'] === UPLOAD_ERR_OK) {
        $tmpPdf = $_FILES["pdf_$index"]['tmp_name'];
        $nombrePdf = basename($_FILES["pdf_$index"]['name']);
        $destinoPdf = $uploadDir . $nombrePdf;
        if (!move_uploaded_file($tmpPdf, $destinoPdf)) {
            $errores[] = "Error al mover archivo PDF $nombrePdf del índice $index";
            $destinoPdf = null;
        }
    }

    // Validación UUID duplicado en 505_factura
    $stmtFacturas = $con->prepare("SELECT idCFactura, 505_04_numFactura FROM 505_factura");
    $stmtFacturas->execute();
    $facturas = $stmtFacturas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($facturas as $factura) {
        if ($factura['505_04_numFactura'] === $uuid) {
            echo json_encode([
                'success' => false,
                'duplicado' => true,
                'uuid' => $uuid,
                'idRegistro' => $factura['idCFactura'],
                'tabla' => '505_factura',
                'mensaje' => "El UUID $uuid ya existe en la tabla 505_factura con el ID {$factura['idCFactura']}."
            ]);
            exit;
        }
    }


    // Validar UUID duplicado en facturas_registradas
    $stmtFacturasReg = $con->prepare("
        SELECT fr.id, fr.uuid, fr.referencia_id, r.Numero AS numero_referencia
        FROM facturas_registradas fr
        LEFT JOIN referencias r ON fr.referencia_id = r.Id
        WHERE fr.uuid = :uuid
    ");
    $stmtFacturasReg->bindParam(':uuid', $uuid);
    $stmtFacturasReg->execute();
    $facturaReg = $stmtFacturasReg->fetch(PDO::FETCH_ASSOC);

    if ($facturaReg) {
        echo json_encode([
            'success' => false,
            'duplicado' => true,
            'uuid' => $uuid,
            'idRegistro' => $facturaReg['id'],
            'referenciaNumero' => $facturaReg['numero_referencia'],
            'tabla' => 'facturas_registradas',
            'mensaje' => "El UUID $uuid ya existe en la tabla facturas_registradas con el ID {$facturaReg['id']} y está vinculado a la referencia {$facturaReg['numero_referencia']}."
        ]);
        exit;
    }

    // Validación básica por archivo
    if (!$folio || !$rfc_proveedor || !$rfc_cliente || !$proveedor || !$cliente || !$fecha) {
        $errores[] = "Archivo #$index con datos incompletos.";
        continue;
    }

    // Insertar factura
    $sql = "INSERT INTO facturas_registradas 
            (folio, rfc_proveedor, proveedor, rfc_cliente, cliente, fecha, importe, uuid, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $con->prepare($sql);
        $resultado = $stmt->execute([
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

        if ($resultado) {
            $insertados++;
            $facturaId = $con->lastInsertId();

            // Insertar archivos relacionados en referencias_archivos
            $archivosParaInsertar = [];
            if ($destinoXml !== null) {
                $archivosParaInsertar[] = ['nombre' => $nombreXml, 'ruta' => $destinoXml];
            }
            if ($destinoPdf !== null) {
                $archivosParaInsertar[] = ['nombre' => $nombrePdf, 'ruta' => $destinoPdf];
            }

            foreach ($archivosParaInsertar as $archivoInfo) {
                $sqlArchivos = "INSERT INTO referencias_archivos (Nombre, Ruta, Solicitud_factura_id) VALUES (?, ?, ?)";
                $stmtArchivo = $con->prepare($sqlArchivos);
                $stmtArchivo->execute([
                    $archivoInfo['nombre'],
                    $archivoInfo['ruta'],
                    $facturaId
                ]);
            }
        } else {
            $errores[] = "Error al insertar el archivo #$index.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error en archivo #$index: " . $e->getMessage();
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
