<?php
session_start();
include('../conexion.php');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

if (isset($_POST['NoSolicitud'], $_POST['SubcuentaId_pago'])) {
    // Recoger todos los valores del form
    $id_solicitud = $_POST['NoSolicitud'];
    $subcuenta_pago = $_POST['SubcuentaId_pago'];
    $observaciones_pago = $_POST['Observaciones_pago'] ?? '';

    function obtenerFechaHoraActual()
    {
        return date("Y-m-d H:i:s");
    }
    //Se actualiza a 2 : Solicitud aprobada
    $sql_update_status = "UPDATE conta_solicitudes SET Status = 2 WHERE Id = :id";
    $stmt = $con->prepare($sql_update_status);
    $stmt->bindParam(':id', $id_solicitud, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener todos los datos de la solicitud aprobada
    $sql_get_solicitud = "SELECT * FROM conta_solicitudes WHERE Id = :id";
    $stmt = $con->prepare($sql_get_solicitud);
    $stmt->bindParam(':id', $id_solicitud, PDO::PARAM_INT);
    $stmt->execute();
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        die("Solicitud no encontrada.");
    }

    // Extraer datos de la solicitud
    $solicitud_id = $solicitud['Id'];
    $referenciaFacturaId = $solicitud['ReferenciaFacturaId'];
    $beneficiario_id = $solicitud['BeneficiarioId'];
    $aduana_id = $solicitud['Aduana'];
    $empresa_id = $solicitud['EmpresaId'];
    $importe = $solicitud['Importe'];
    $fecha = $solicitud['Fecha'];
    $status = $solicitud['Status'];
    $fecha_alta = $solicitud['FechaAlta'];
    $usuario_alta = $solicitud['UsuarioAlta'];

    // Generar el numero de poliza, siempre cheque
    $prefijo = 'C'; // Cheques
    $sql_ultimo = "SELECT Numero FROM conta_polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
    $stmt_ultimo = $con->prepare($sql_ultimo);
    $stmt_ultimo->execute([$prefijo]);

    $ultimo_numero = 0;
    if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
        $ultimo_numero = (int) substr($fila['Numero'], 1); // Extraer número sin prefijo
    }

    $nuevo_numero = $ultimo_numero + 1;
    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

    // Variables para insertar
    $fecha_alta_default = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];
    $exportadoCoi = 1;

    $sql_insertar_poliza = "INSERT INTO conta_polizas 
        (SolicitudId, BeneficiarioId, EmpresaId, Numero, Importe, Fecha, ExportadoCoi, Activo, FechaAlta, UsuarioAlta, Aduana)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_data = $con->prepare($sql_insertar_poliza);

    $resultado = $stmt_data->execute([
        $solicitud_id,
        $beneficiario_id,
        $empresa_id,
        $numero_poliza,
        $importe,
        $fecha,
        $exportadoCoi,
        $activo,
        $fecha_alta_default,
        $usuarioAlta,
        $aduana_id
    ]);

    if ($resultado) {
        $poliza_id = $con->lastInsertId();

        // Obtener las partidas originales
        $sql_partidas = "SELECT * 
             FROM conta_partidassolicitudes 
             WHERE SolicitudId = :id_solicitud";

        $stmt_partidas = $con->prepare($sql_partidas);
        $stmt_partidas->bindParam(':id_solicitud', $id_solicitud, PDO::PARAM_INT);
        $stmt_partidas->execute();

        $partidas = $stmt_partidas->fetchAll(PDO::FETCH_ASSOC);

        // Insertar las partidas de la solicitud
        $sql_insertar_partidas = "INSERT INTO conta_partidaspolizas 
    (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Pagada, Activo, Observaciones, NumeroFactura, UsuarioSolicitud)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_data = $con->prepare($sql_insertar_partidas);

        $abono = 0;

        foreach ($partidas as $partida) {
            $stmt_data->execute([
                $poliza_id,
                $partida['SubcuentaId'],
                $partida['ReferenciaId'],
                $partida['Importe'],
                $abono,
                1,
                $activo,
                $partida['Observaciones'],
                $partida['NumeroFactura'],
                $partida['Created_by']
            ]);

            // --- Actualizar saldo de la subcuenta ---
            $sql_actualizar_saldo = "UPDATE cuentas SET Saldo = Saldo + :cargo - :abono WHERE Id = :subcuentaId";
            $stmt_saldo = $con->prepare($sql_actualizar_saldo);
            $stmt_saldo->execute([
                ':cargo' => $partida['Importe'],
                ':abono' => $abono,
                ':subcuentaId' => $partida['SubcuentaId']
            ]);
        }

        // Verificar si la cuenta de pago empieza con '113'
        $sql_verificar_cuenta = "SELECT Numero FROM cuentas WHERE Id = ? LIMIT 1";
        $stmt_verificar = $con->prepare($sql_verificar_cuenta);
        $stmt_verificar->execute([$subcuenta_pago]);
        $numeroCuenta = $stmt_verificar->fetchColumn();

        $pagada = 0; // valor por defecto
        if ($numeroCuenta !== false && strpos($numeroCuenta, '113') === 0) {
            $pagada = 1;
        }

        // Insertar la partida de pago con la validación de pagada
        $cargo = 0;
        $sql_insertar_pago = "INSERT INTO conta_partidaspolizas
    (PolizaId, SubcuentaId, Cargo, Abono, Pagada, Observaciones, Activo, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_data = $con->prepare($sql_insertar_pago);
        $stmt_data->execute([
            $poliza_id,
            $subcuenta_pago,
            $cargo,
            $importe,
            $pagada,
            $observaciones_pago,
            $activo,
            $usuarioAlta
        ]);

        // --- Actualizar saldo de la subcuenta de pago ---
        $sql_actualizar_saldo_pago = "UPDATE cuentas SET Saldo = Saldo + :cargo - :abono WHERE Id = :subcuentaId";
        $stmt_saldo_pago = $con->prepare($sql_actualizar_saldo_pago);
        $stmt_saldo_pago->execute([
            ':cargo' => $cargo,
            ':abono' => $importe,
            ':subcuentaId' => $subcuenta_pago
        ]);
        // También actualizamos la póliza según si la cuenta empieza con 113
        $stmt_update_poliza = $con->prepare("UPDATE conta_polizas SET Pagada = ? WHERE Id = ?");
        $stmt_update_poliza->execute([$pagada, $poliza_id]);

        // Obtener la última partida de esa póliza
        $sql_ultima_partida = "SELECT Pagada FROM conta_partidaspolizas WHERE PolizaId = ? ORDER BY Partida DESC LIMIT 1";
        $stmt_ultima = $con->prepare($sql_ultima_partida);
        $stmt_ultima->execute([$poliza_id]);
        $ultima_partida = $stmt_ultima->fetch(PDO::FETCH_ASSOC);

        // Si la última partida es no pagada, actualizar todas las partidas de esa póliza a Pagada = 0
        if ($ultima_partida && $ultima_partida['Pagada'] == 0) {
            $stmt_actualizar_todas = $con->prepare("UPDATE conta_partidaspolizas SET Pagada = 0 WHERE PolizaId = ?");
            $stmt_actualizar_todas->execute([$poliza_id]);
        }

    } else {
        echo "Error al guardar la póliza.";
    }

    // 1. Obtener todas las referencias de la solicitud
    $sql_refs = "SELECT DISTINCT ReferenciaId 
                FROM conta_partidassolicitudes 
                WHERE SolicitudId = :solicitudId";
    $stmt_refs = $con->prepare($sql_refs);
    $stmt_refs->execute([':solicitudId' => $id_solicitud]);
    $referencias = $stmt_refs->fetchAll(PDO::FETCH_COLUMN);

    foreach ($referencias as $refId) {
        // 2. Obtener todas las facturas con esa referencia
        $sql_facturas = "SELECT Id FROM conta_facturas_registradas WHERE referencia_id = :refId";
        $stmt_facturas = $con->prepare($sql_facturas);
        $stmt_facturas->execute([':refId' => $refId]);
        $facturaIds = $stmt_facturas->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($facturaIds)) {
            // Construir placeholders dinámicos
            $placeholders = implode(',', array_fill(0, count($facturaIds), '?'));

            // 3. Actualizar los archivos de esas facturas con la referencia correspondiente
            $sql_update_ref_archivos = "
                UPDATE conta_referencias_archivos
                SET Referencia_id = ?
                WHERE Solicitud_factura_id IN ($placeholders)
            ";

            $params = array_merge([$refId], $facturaIds);
            $stmt_update_ref = $con->prepare($sql_update_ref_archivos);
            $stmt_update_ref->execute($params);
        }
}

    echo json_encode([
        'success' => true,
        'mensaje' => 'Pago guardado correctamente.',
        'numero' => $numero_poliza
    ]);


} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al guardar la póliza: ' . implode(", ", $stmt_poliza->errorInfo())
    ]);
    exit;
}
?>