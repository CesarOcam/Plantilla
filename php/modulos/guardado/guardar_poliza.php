<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
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
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }
    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;
    $exportadoCoi = 1;


    $tipo_poliza = $_POST['tipo'] ?? null;

if (!empty($tipo_poliza) && is_numeric($tipo_poliza)) {
    switch ((int)$tipo_poliza) {
        case 1: $prefijo = 'C'; break;
        case 2: $prefijo = 'D'; break;
        case 3: $prefijo = 'I'; break;
        case 4: $prefijo = 'E'; break;
        default: $prefijo = 'X'; break;
    }

    $sql = "
        SELECT Numero
        FROM polizas
        WHERE LEFT(Numero, 1) = ?
        ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC
        LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute([$prefijo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $ultimo_numero = (int) substr($row['Numero'], 1);
        $nuevo_numero = $ultimo_numero + 1;
    } else {
        $nuevo_numero = 1;
    }

    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

} else {
    die("Tipo de póliza no especificado o inválido.");
}

// Luego usa $numero_poliza en tu insert

    // Asegurarse de que todos los campos coincidan con los de la base de datos
   $sql_insert_poliza = "INSERT INTO polizas 
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

    // Obtener el ID generado de la póliza para vincular partidas
    $poliza_id = $con->lastInsertId();
    $activo = 1;
    // Preparar inserción de partidas
    $sql_insert_partidas = "INSERT INTO partidaspolizas 
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
        $factura,     // FolioArchivo
        $activo,
        $factura      // NumeroFactura
    ]);
}


    echo "Poliza guardada correctamente.";

} else {
    echo "Faltan datos obligatorios.";
}
?>

