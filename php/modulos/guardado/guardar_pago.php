<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['NoSolicitud'], $_POST['SubcuentaId_pago'])) {
    // Recoger todos los valores del form
    $id_solicitud = $_POST['NoSolicitud'];
    $subcuenta_pago = $_POST['SubcuentaId_pago'];
    $observaciones = $_POST['Observaciones_pago'] ?? ''; // Puede venir vacío

    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }

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
    $poliza_id      = $solicitud['PolizaId'];
    $beneficiario_id = $solicitud['BeneficiarioId'];
    $aduana_id      = $solicitud['AduanaId'];
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
    $usuarioAlta = 1;
    $exportadoCoi = 1;

    // Insertar solicitud aprobada en tabla polizas
    $sql_insertar_poliza = "INSERT INTO polizas 
        (BeneficiarioId, EmpresaId, Numero, Importe, Fecha, ExportadoCoi, Activo, FechaAlta, UsuarioAlta)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_data = $con->prepare($sql_insertar_poliza);

    $resultado = $stmt_data->execute([
        $beneficiario_id,
        $empresa_id,
        $numero_poliza,
        $importe,
        $fecha,
        $exportadoCoi,
        $activo,
        $fecha_alta_default,
        $usuarioAlta
    ]);

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