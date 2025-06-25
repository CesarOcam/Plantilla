<?php
session_start();
include('../conexion.php');
header('Content-Type: application/json');

// Validar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}
$usuarioAlta = $_SESSION['usuario_id'];

//Leer y decodificar los datos JSON------------------------------
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validación de estructura básica
if (!isset($data['archivos']) || !is_array($data['archivos'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos inválidos: se esperaba un arreglo de archivos.'
    ]);
    exit;
}

$errores = [];
$insertados = 0;

foreach ($data['archivos'] as $index => $archivo) {
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

    //Se obtienen datos de la tabla 505_factura para validar uuid--------------
// 1. Verificar en 505_factura
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

    // 2. Verificar en facturas_registradas
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


    //---------------------------------------------------------------------------------------------------------

    // Validación básica por archivo
    if (!$folio || !$rfc_proveedor || !$rfc_cliente || !$proveedor || !$cliente || !$fecha) {
        $errores[] = "Archivo #$index con datos incompletos.";
        continue;
    }

    $rfc_cliente;

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
