<?php
session_start();
include('../conexion.php');

error_log("===== INICIO guardar_factura_solicitud.php =====");

$usuarioUpdate = $_SESSION['usuario_id'] ?? null;
if (!$usuarioUpdate) {
    error_log("ERROR: usuario_id no encontrado en sesión.");
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

date_default_timezone_set('America/Mexico_City');
$fechaUpdate = date("Y-m-d H:i:s");

$status = 1;

if (
    isset($_POST['factura_id'], $_POST['referencia_id'], $_POST['subcuentas']) &&
    is_array($_POST['factura_id']) &&
    is_array($_POST['referencia_id']) &&
    is_array($_POST['subcuentas'])
) {
    error_log("Datos POST recibidos: factura_id=[" . implode(',', $_POST['factura_id']) . "], referencia_id=[" . implode(',', $_POST['referencia_id']) . "], subcuentas=[" . implode(',', $_POST['subcuentas']) . "]");
    try {
        $con->beginTransaction();

        // 1. Actualizar facturas
        $stmtUpdate = $con->prepare("
            UPDATE facturas_registradas 
            SET referencia_id = ?, subcuenta_id = ?, usuario_update = ?, fecha_solicitud = ?, status = ?
            WHERE Id = ? AND status != 2
        ");

        foreach ($_POST['factura_id'] as $index => $facturaId) {
            $referenciaId = (int) ($_POST['referencia_id'][$index] ?? 0);
            $subcuentaId = (int) ($_POST['subcuentas'][$index] ?? 0);

            if ($facturaId > 0 && $referenciaId > 0) {
                error_log("Actualizando factura Id=$facturaId con referencia_id=$referenciaId y subcuenta_id=$subcuentaId");
                $stmtUpdate->execute([$referenciaId, $subcuentaId, $usuarioUpdate, $fechaUpdate, $status, $facturaId]);
                $rowsAffected = $stmtUpdate->rowCount();
                error_log("Filas afectadas en UPDATE factura: $rowsAffected");
            } else {
                error_log("Datos inválidos para factura en índice $index: facturaId=$facturaId, referenciaId=$referenciaId");
            }
        }

        // 2. Obtener datos para las solicitudes
        $stmtFacturas = $con->prepare("
            SELECT f.Id AS factura_id, f.status, f.proveedor, f.importe, f.referencia_id, f.fecha_solicitud AS fecha,
                f.uuid, f.subcuenta_id, b.Id AS beneficiario_id, r.AduanaId AS aduana_id
            FROM facturas_registradas f
            LEFT JOIN (
                SELECT MIN(Id) AS Id, Rfc
                FROM beneficiarios
                GROUP BY Rfc
            ) b ON b.Rfc = f.rfc_proveedor
            LEFT JOIN referencias r ON r.Id = f.referencia_id
            WHERE f.status = 1
        ");

        $stmtFacturas->execute();
        $facturasInfo = $stmtFacturas->fetchAll(PDO::FETCH_ASSOC);

        error_log("Facturas con status=1 encontradas: " . count($facturasInfo));
        foreach ($facturasInfo as $factura) {
            error_log("Factura ID: {$factura['factura_id']}, Status: {$factura['status']}, BeneficiarioId: {$factura['beneficiario_id']}, AduanaId: {$factura['aduana_id']}");
        }

        // Preparar las consultas
        $stmtSolicitud = $con->prepare("
        INSERT INTO solicitudes (BeneficiarioId, EmpresaId, Importe, Aduana, Fecha, status, FechaAlta, UsuarioAlta)
        VALUES (?, 2, ?, ?, ?, 1, ?, ?)
");

        $stmtPartida = $con->prepare("
        INSERT INTO partidassolicitudes (Partida, SolicitudId, SubcuentaId, ReferenciaId, Importe, UuidArchivoFactura)
        VALUES (0, ?, ?, ?, ?, ?)
");

        $stmtFacturaStatus = $con->prepare("UPDATE facturas_registradas SET status = 2 WHERE Id = ?");

        $stmtActualizarSolicitudFacturaId = $con->prepare("
        UPDATE solicitudes 
        SET ReferenciaFacturaId = :referenciaId 
        WHERE Id = :solicitudId
");

        error_log("Iniciando ciclo de inserción, total facturas: " . count($facturasInfo));

        // Array temporal para acumular solicitudId y referenciaId
        $actualizaciones = [];

        foreach ($facturasInfo as $factura) {
            error_log("Procesando factura con ID: {$factura['factura_id']}");

            if ($factura['beneficiario_id'] && $factura['aduana_id']) {
                // Insertar solicitud
                $stmtSolicitud->execute([
                    $factura['beneficiario_id'],
                    $factura['importe'],
                    $factura['aduana_id'],
                    $factura['fecha'],
                    $fechaUpdate,
                    $usuarioUpdate
                ]);
                $solicitudId = $con->lastInsertId();

                // Insertar partida
                $stmtPartida->execute([
                    $solicitudId,
                    $factura['subcuenta_id'],
                    $factura['referencia_id'],
                    $factura['importe'],
                    $factura['uuid']
                ]);

                // Guardar par para actualización posterior
                $actualizaciones[] = [
                    'solicitudId' => $solicitudId,
                    'referenciaId' => $factura['referencia_id']
                ];

                // Actualizar estatus de la factura
                $stmtFacturaStatus->execute([$factura['factura_id']]);

            } else {
                error_log("Factura ID {$factura['factura_id']} no tiene beneficiario_id o aduana_id válido");
            }
        }

        // Ejecutar actualizaciones fuera del foreach
        foreach ($actualizaciones as $pair) {
            $stmtActualizarSolicitudFacturaId->execute([
                ':referenciaId' => $pair['referenciaId'],
                ':solicitudId' => $pair['solicitudId']
            ]);
            error_log("Actualizado SolicitudFacturaId para solicitudId={$pair['solicitudId']}, referenciaId={$pair['referenciaId']}");
        }


        $con->commit();
        error_log("Transacción COMMIT exitosa.");
        echo json_encode(['success' => true, 'message' => 'Facturas y solicitudes procesadas correctamente']);
        exit;



    } catch (PDOException $e) {
        $con->rollBack();
        error_log("Error en transacción: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
} else {
    error_log("Datos inválidos o incompletos en POST.");
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o incompletos']);
    exit;
}

error_log("===== FIN guardar_factura_solicitud.php =====");
