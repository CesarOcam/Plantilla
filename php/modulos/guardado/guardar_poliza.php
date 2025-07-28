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

if (isset($_POST['beneficiario'])) {
    // Recoger todos los valores
    $empresa = 2;
    $tipo = $_POST['tipo'];
    $beneficiario = trim($_POST['beneficiario']);
    $fecha = $_POST['fecha'];
    $concepto = $_POST['concepto'];

    //Recibe datos de las subcuentas
    $subcuentas = $_POST['Subcuenta'] ?? [];
    $cargos = $_POST['Cargo'] ?? [];
    $abonos = $_POST['Abono'] ?? [];
    $observaciones = $_POST['Observaciones'] ?? [];
    $facturas = $_POST['Factura'] ?? [];
    $total_cargos = 0.0;
    $total_abonos = 0.0;


    foreach ($cargos as $c) {
        $total_cargos += is_numeric($c) ? floatval($c) : 0;
    }
    foreach ($abonos as $a) {
        $total_abonos += is_numeric($a) ? floatval($a) : 0;
    }
    $importe = $total_cargos;

    function obtenerFechaHoraActual()
    {
        return date("Y-m-d H:i:s"); 
    }
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];
    $exportadoCoi = 1;

    $numero_poliza = '';
    if (!empty($tipo)) {
        $tipo_int = (int) $tipo;
        $prefijo = match ($tipo_int) {
            1 => 'C',
            2 => 'D',
            3 => 'I',
            4 => 'E',
            default => 'X',
        };

        $sql_ultimo = "SELECT Numero FROM conta_polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
        $stmt_ultimo = $con->prepare($sql_ultimo);
        $stmt_ultimo->execute([$prefijo]);

        $ultimo_numero = 0;
        if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
            $ultimo_numero = (int) substr($fila['Numero'], 1); // Extraer número sin prefijo
        }

        $nuevo_numero = $ultimo_numero + 1;
        $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);
    }

    $sql_insert_poliza = "INSERT INTO conta_polizas 
    (
        BeneficiarioId, EmpresaId, Numero, Importe, Concepto, Fecha, ExportadoCoi, Activo, FechaAlta, UsuarioAlta
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $beneficiario,
        $empresa,
        $numero_poliza,
        $importe,
        $concepto,
        $fecha,
        $exportadoCoi,
        $activo,
        $fecha_alta,
        $usuarioAlta
    ];

    $stmt_poliza = $con->prepare($sql_insert_poliza);
    $resultado = $stmt_poliza->execute($params);


    if (!$resultado) {
        die("Error al guardar la póliza: " . implode(", ", $stmt_poliza->errorInfo()));
    }

    $poliza_id = $con->lastInsertId();
    $activo = 1;
    // Preparar inserción de partidas
    $sql_insert_partidas = "INSERT INTO conta_partidaspolizas 
    (Polizaid, Subcuentaid, Cargo, Abono, Observaciones, FolioArchivo, Activo, NumeroFactura)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_partidas = $con->prepare($sql_insert_partidas);

    foreach ($subcuentas as $i => $subcuenta_id) {
        $cargo = isset($cargos[$i]) && is_numeric($cargos[$i]) ? floatval($cargos[$i]) : 0;
        $abono = isset($abonos[$i]) && is_numeric($abonos[$i]) ? floatval($abonos[$i]) : 0;
        $observacion = $observaciones[$i] ?? '';
        $factura = $facturas[$i] ?? '';

        $stmt_partidas->execute([
            $poliza_id,
            $subcuenta_id,
            $cargo,
            $abono,
            $observacion,
            $factura,
            $activo,
            $factura
        ]);
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Póliza guardada correctamente.',
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