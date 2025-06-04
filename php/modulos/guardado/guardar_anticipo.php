<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['aduana'], $_POST['beneficiario'], $_POST['Subcuenta'], $_POST['Cargo'], $_POST['Abono'])) {
    // Recoger todos los valores
    $empresa = 2;
    $beneficiario = trim($_POST['beneficiario']);
    $fecha = $_POST['fecha'];

    //Recibe datos de las subcuentas
    $subcuentas = $_POST['Subcuenta'] ?? [];
    $referencias = $_POST['Referencia'] ?? [];
    $cargos = $_POST['Cargo'] ?? [];
    $abonos = $_POST['Abono'] ?? [];
    $observaciones = $_POST['Observaciones'] ?? [];
    $facturas = $_POST['Factura'] ?? [];
    $total_cargos = 0.0;
    $total_abonos = 0.0;

    // Sumar cargos
    foreach ($cargos as $c) {
        $total_cargos += is_numeric($c) ? floatval($c) : 0;
    }

    // Sumar abonos
    foreach ($abonos as $a) {
        $total_abonos += is_numeric($a) ? floatval($a) : 0;
    }
    $importe = $total_cargos;

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual()
    {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }
    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;
    $exportadoCoi = 1;


    $prefijo = 'I';
    $numero_poliza = '';

    $sql_ultimo = "SELECT Numero FROM polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
    $stmt_ultimo = $con->prepare($sql_ultimo);
    $stmt_ultimo->execute([$prefijo]);

    $ultimo_numero = 0;
    if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
        $ultimo_numero = (int) substr($fila['Numero'], 1); // Extraer número sin prefijo
    }

    $nuevo_numero = $ultimo_numero + 1;
    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);



    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql_insert_poliza = "INSERT INTO polizas 
    (
        BeneficiarioId, EmpresaId, Numero, Importe, Fecha, ExportadoCoi, Activo, FechaAlta, UsuarioAlta
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $beneficiario,
        $empresa,
        $numero_poliza,
        $importe,
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

    // Obtener el ID generado de la póliza para vincular partidas
    $poliza_id = $con->lastInsertId();
    $activo = 1;
    // Preparar inserción de partidas
    $sql_insert_partidas = "INSERT INTO partidaspolizas 
    (Polizaid, Subcuentaid, ReferenciaId, Cargo, Abono, Observaciones, FolioArchivo, Activo, NumeroFactura)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_partidas = $con->prepare($sql_insert_partidas);

    foreach ($subcuentas as $i => $subcuenta_id) {
        $cargo = isset($cargos[$i]) && is_numeric($cargos[$i]) ? floatval($cargos[$i]) : 0;
        $abono = isset($abonos[$i]) && is_numeric($abonos[$i]) ? floatval($abonos[$i]) : 0;

        $referencia = $referencias[$i] ?? '';
        if (!is_numeric($referencia) || $referencia === '') {
            $referencia = null;
        } else {
            $referencia = (int) $referencia;
        }

        $observacion = $observaciones[$i] ?? '';
        $factura = $facturas[$i] ?? '';

        $stmt_partidas->execute([
            $poliza_id,
            $subcuenta_id,
            $referencia,
            $cargo,
            $abono,
            $observacion,
            $factura,     // FolioArchivo
            $activo,
            $factura      // NumeroFactura
        ]);
    }



    echo json_encode([
        'success' => true,
        'mensaje' => 'Anticipo guardado correctamente.',
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