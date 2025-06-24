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

    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    //Se actualiza a 2 : Solicitud aprobada
    $sql_update_status ="UPDATE solicitudes SET Status = 2 WHERE Id = :id";
    $stmt = $con->prepare($sql_update_status);
    $stmt->bindParam(':id', $id_solicitud, PDO::PARAM_INT);
    $stmt->execute();

    // Obtener todos los datos de la solicitud aprobada
    $sql_get_solicitud = "SELECT * FROM solicitudes WHERE Id = :id";
    $stmt = $con->prepare($sql_get_solicitud);
    $stmt->bindParam(':id', $id_solicitud, PDO::PARAM_INT);
    $stmt->execute();
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        die("Solicitud no encontrada.");
    }

    // Extraer datos de la solicitud
    $solicitud_id    = $solicitud['Id'];
    $beneficiario_id = $solicitud['BeneficiarioId'];
    $aduana_id      = $solicitud['Aduana'];
    $empresa_id     = $solicitud['EmpresaId'];
    $importe        = $solicitud['Importe'];
    $fecha          = $solicitud['Fecha'];
    $status         = $solicitud['Status'];
    $fecha_alta     = $solicitud['FechaAlta'];
    $usuario_alta   = $solicitud['UsuarioAlta'];

    // Generar el numero de poliza, siempre cheque
    $prefijo = 'C'; // Cheques
    $sql_ultimo = "SELECT Numero FROM polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
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

    $sql_insertar_poliza = "INSERT INTO polizas 
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

    $sql_partidas = "SELECT * 
                 FROM partidassolicitudes 
                 WHERE SolicitudId = :id_solicitud";

    $stmt_partidas = $con->prepare($sql_partidas);
    $stmt_partidas->bindParam(':id_solicitud', $id_solicitud, PDO::PARAM_INT);
    $stmt_partidas->execute();

    $partidas = $stmt_partidas->fetchAll(PDO::FETCH_ASSOC);
   
    
    $sql_insertar_partidas = "INSERT INTO partidaspolizas 
        (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, NumeroFactura)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_data = $con->prepare($sql_insertar_partidas);

    $abono = 0;
    foreach ($partidas as $partida) {
    $stmt_data->execute([
        $poliza_id,
        $partida['SubcuentaId'],
        $partida['ReferenciaId'],
        $partida['Importe'],
        $abono,
        $partida['Observaciones'],
        $activo,
        $partida['NumeroFactura'],
        ]);
    }

    $cargo = 0;
    $sql_insertar_pago = "INSERT INTO partidaspolizas
    (PolizaId, SubcuentaId, Cargo, Abono, Observaciones, Activo)
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_data = $con->prepare($sql_insertar_pago);
    $pago = $stmt_data->execute([
        $poliza_id,
        $subcuenta_pago,
        $cargo,
        $importe,
        $observaciones_pago,
        $activo

    ]);

    } else {

        echo "Error al guardar la póliza.";
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