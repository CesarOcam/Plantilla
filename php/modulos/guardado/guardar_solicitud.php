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

if (isset($_POST['beneficiario'], $_POST['Referencia'], $_POST['aduana'])) {
    $empresa = 2;
    $tipo = 1;
    $beneficiario = trim($_POST['beneficiario']);
    $aduana = trim($_POST['aduana']);

    $subcuentas = $_POST['Subcuenta'] ?? [];
    $referencias = $_POST['Referencia'] ?? [];
    $cargos = $_POST['Cargo'] ?? [];
    $abonos = $_POST['Abono'] ?? [];
    $observaciones = $_POST['Observaciones'] ?? [];

    $total_cargos = 0.0;
    $total_abonos = 0.0;

    foreach ($cargos as $c) {
        $total_cargos += is_numeric($c) ? floatval($c) : 0;
    }
    foreach ($abonos as $a) {
        $total_abonos += is_numeric($a) ? floatval($a) : 0;
    }

    $importe = $total_cargos;
    $fecha_alta = date("Y-m-d H:i:s");
    $fecha = date("Y-m-d H:i:s");
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];
    $exportadoCoi = 1;
    $tipo_poliza = 1;
    $referencia_id = 2;

    $sql_insert_poliza = "INSERT INTO solicitudes 
        (BeneficiarioId, Aduana, EmpresaId, Importe, Fecha, Status, FechaAlta, UsuarioAlta)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [$beneficiario, $aduana, $empresa, $importe, $fecha, $activo, $fecha_alta, $usuarioAlta];

    $stmt_poliza = $con->prepare($sql_insert_poliza);
    $resultado = $stmt_poliza->execute($params);

    if (!$resultado) {
        die("Error al guardar la póliza: " . implode(", ", $stmt_poliza->errorInfo()));
    }

    $solicitud_id = $con->lastInsertId();
    $partida = 0;

    $sql_insert_partidas = "INSERT INTO partidassolicitudes
        (Partida, SolicitudId, Subcuentaid, ReferenciaId, Importe, Observaciones)
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_partidas = $con->prepare($sql_insert_partidas);

    foreach ($subcuentas as $i => $subcuenta_id) {
        $referencia = isset($referencias[$i]) && is_numeric($referencias[$i]) ? floatval($referencias[$i]) : 0;
        $cargo = isset($cargos[$i]) && is_numeric($cargos[$i]) ? floatval($cargos[$i]) : 0;
        $abono = isset($abonos[$i]) && is_numeric($abonos[$i]) ? floatval($abonos[$i]) : 0;
        $observacion = $observaciones[$i] ?? '';
        $factura = $facturas[$i] ?? '';

        $stmt_partidas->execute([
            $partida,
            $solicitud_id,
            $subcuenta_id,
            $referencia,
            $cargo,
            $observacion,
        ]);
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Póliza guardada correctamente.',
    ]);
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan datos obligatorios.'
    ]);
    exit;
}
?>
