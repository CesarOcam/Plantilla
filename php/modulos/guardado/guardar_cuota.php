<?php
session_start();
include('../conexion.php');
header('Content-Type: application/json');

// Obtener usuario_id de la sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

$usuarioAlta = $_SESSION['usuario_id'];

// Recibir datos del formulario
$iva = $_POST['IVA'] ?? '';
$subtotal = $_POST['subtotal'] ?? [];
$cargos = $_POST['monto'] ?? [];
$referencia = $_POST['referencia'] ?? '';
$aduana = $_POST['aduanaHidden'] ?? '';
$observaciones = $_POST['observaciones'] ?? [];
$archivos = $_FILES['facturas'] ?? [];

// Quitar comas y convertir a float para las variables numéricas
$iva = is_string($iva) ? (float) str_replace(',', '', $iva) : 0.0;
$subtotal = is_string($subtotal) ? (float) str_replace(',', '', $subtotal) : 0.0;
$cargos = is_string($cargos) ? (float) str_replace(',', '', $cargos) : 0.0;
// Validar referencia
if (!$referencia) {
    echo json_encode([
        'success' => false,
        'message' => 'Referencia no proporcionada.',
    ]);
    exit;
}

// Calcular total de cargos
$total_cargos = 0.0;

if (is_array($cargos)) {
    foreach ($cargos as $c) {
        $total_cargos += is_numeric($c) ? floatval($c) : 0;
    }
} else {
    $total_cargos += is_numeric($cargos) ? floatval($cargos) : 0;
}

$importe = $total_cargos;

$total_abono = floatval($subtotal) + floatval($iva);

// Validar que el total de cargos sea igual al total_abono
if (abs($total_abono - $total_cargos) > 0.01) {
    echo json_encode([
        'success' => false,
        'message' => 'El total de los cargos (' . number_format($total_cargos, 2) . ') no coincide con el subtotal (' . number_format($subtotal, 2) . ') + IVA (' . number_format($total_abono, 2) . ').'
    ]);
    exit;
}

// Datos estáticos
$zonaMexico = new DateTimeZone('America/Mexico_City');
$fecha = (new DateTime('now', $zonaMexico))->format('Y-m-d H:i:s');
$fecha_alta = (new DateTime('now', $zonaMexico))->format('Y-m-d H:i:s');
$activo = 1;
$exportadoCoi = 1;
$empresa = 2;

//---------------------Generar Número de Póliza D-----------------------------
$prefijo = 'D';
$numero_poliza = '';

$sql_ultimo = "SELECT Numero FROM conta_polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
$stmt_ultimo = $con->prepare($sql_ultimo);
$stmt_ultimo->execute([$prefijo]);

$ultimo_numero = 0;
if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
    $ultimo_numero = (int) substr($fila['Numero'], 1);
}

$nuevo_numero = $ultimo_numero + 1;
$numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

//---------------------Guardar la Póliza-----------------------------
$sql_insert_poliza = "INSERT INTO conta_polizas 
    (EmpresaId, BeneficiarioId, Numero, Importe, Fecha, ExportadoCoi, Activo, FechaAlta, UsuarioAlta)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params = [
    $empresa,
    505,
    $numero_poliza,
    $importe,
    $fecha,
    $exportadoCoi,
    $activo,
    $fecha_alta,
    $usuarioAlta
];

try {
    $stmt_poliza = $con->prepare($sql_insert_poliza);
    $resultado = $stmt_poliza->execute($params);

    if (!$resultado) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la póliza en la base de datos.',
            'error_info' => $stmt_poliza->errorInfo()
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Excepción al guardar la póliza.',
        'error' => $e->getMessage()
    ]);
    exit;
}

//--------------------Guardar subcuentas 1-----------------------------
$poliza_id = $con->lastInsertId();
$abono = 0;
switch ((int) $aduana) {
    case 74: //VERACRUZ
        $subcuenta = 35;
        break;
    case 25: //MANZANILLO
        $subcuenta = 36;
        break;
    case 119: //ALTAMIRA
        $subcuenta = 37;
        break;
    case 124: //AIFA
        $subcuenta = 38;
        break;
    case 91: //LAZARO CARDENAS
        $subcuenta = 40;
        break;
    case 81: //CDMX
        $subcuenta = 39;
        break;
    case 125: //LOGISTICA
        $subcuenta = 576;
        break;
    default:
        $subcuenta = 0;
        break;
}

$sql_insert_partida = "INSERT INTO conta_partidaspolizas 
    (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, created_at, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params = [
    $poliza_id,
    $subcuenta,
    $referencia,
    $cargos,
    $abono,
    $observaciones,
    $activo,
    $fecha_alta,
    $usuarioAlta
];

// --- Actualizar saldo de la subcuenta ---
$sql_actualizar_saldo = "UPDATE cuentas SET Saldo = Saldo + :cargo - :abono WHERE Id = :subcuentaId";
$stmt_saldo = $con->prepare($sql_actualizar_saldo);
$stmt_saldo->execute([
    ':cargo' => $cargos,
    ':abono' => $abono,
    ':subcuentaId' => $subcuenta
]);

try {
    $stmt_partida = $con->prepare($sql_insert_partida);
    $resultado = $stmt_partida->execute($params);

    if (!$resultado) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la póliza en la base de datos.',
            'error_info' => $stmt_partida->errorInfo()
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Excepción al guardar la póliza.',
        'error' => $e->getMessage()
    ]);
    exit;
}

//--------------------Guardar subcuentas 2-------------------------------
$cargos = 0;
switch ((int) $aduana) {
    case 74:
        $subcuenta = 237; //VERACRUZ
        break;
    case 25:
        $subcuenta = 238; //MANZANILLO
        break;
    case 119:
        $subcuenta = 239; //ALTAMIRA
        break;
    case 124: //AIFA
        $subcuenta = 240; //REYNOSA
        break;
    case 81://CDMX
        $subcuenta = 241; //CDMX
        break;
    case 91://LAZARO
        $subcuenta = 242; //LAZARO
        break;
    case 125://LOGISTICA
        $subcuenta = 577; //LOGISTICA
        break;
    default:
        $subcuenta = 0;
        break;
}

$sql_insert_partida2 = "INSERT INTO conta_partidaspolizas 
        (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params = [
    $poliza_id,
    $subcuenta,
    $referencia,
    $cargos,
    $subtotal,
    $observaciones,
    $activo,
    $fecha_alta,
    $usuarioAlta
];

// --- Actualizar saldo de la subcuenta ---
$sql_actualizar_saldo = "UPDATE cuentas SET Saldo = Saldo + :cargo - :abono WHERE Id = :subcuentaId";
$stmt_saldo = $con->prepare($sql_actualizar_saldo);
$stmt_saldo->execute([
    ':cargo' => $cargos,
    ':abono' => $subtotal,
    ':subcuentaId' => $subcuenta
]);

try {
    $stmt_partida2 = $con->prepare($sql_insert_partida2);
    $resultado = $stmt_partida2->execute($params);

    if (!$resultado) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la póliza en la base de datos.',
            'error_info' => $stmt_partida2->errorInfo()
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Excepción al guardar la póliza.',
        'error' => $e->getMessage()
    ]);
    exit;
}

//--------------------Guardar subcuentas 3-------------------------------
$cargos = 0;
$subcuenta = 182;

$sql_insert_partida3 = "INSERT INTO conta_partidaspolizas 
    (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, created_at, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params = [
    $poliza_id,
    $subcuenta,
    $referencia,
    $cargos,
    $iva,
    $observaciones,
    $activo,
    $fecha_alta,
    $usuarioAlta
];

// --- Actualizar saldo de la subcuenta ---
$sql_actualizar_saldo = "UPDATE cuentas SET Saldo = Saldo + :cargo - :abono WHERE Id = :subcuentaId";
$stmt_saldo = $con->prepare($sql_actualizar_saldo);
$stmt_saldo->execute([
    ':cargo' => $cargos,
    ':abono' => $iva,
    ':subcuentaId' => $subcuenta
]);

try {
    $stmt_partida3 = $con->prepare($sql_insert_partida3);
    $resultado = $stmt_partida3->execute($params);

    if (!$resultado) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la póliza en la base de datos.',
            'error_info' => $stmt_partida3->errorInfo()
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Excepción al guardar la póliza.',
        'error' => $e->getMessage()
    ]);
    exit;
}

//---------------------Guardar los Archivos-----------------------------
$basePath = realpath(__DIR__ . '/../../../docs'); // ruta absoluta donde se guardan los archivos
if (!$basePath) {
    echo json_encode([
        'success' => false,
        'message' => 'Ruta base "docs" no encontrada en el servidor.'
    ]);
    exit;
}

$targetDir = $basePath . '/' . $referencia;
if (!file_exists($targetDir)) {
    if (!mkdir($targetDir, 0777, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear la carpeta de destino: ' . $targetDir,
        ]);
        exit;
    }
}

$archivosGuardados = [];

if (is_array($archivos['name'])) {
    for ($i = 0; $i < count($archivos['name']); $i++) {
        if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $archivos['tmp_name'][$i];
            $nombreOriginal = basename($archivos['name'][$i]);
            $nombreFinal = uniqid() . "_" . $nombreOriginal;
            $rutaFisica = $targetDir . '/' . $nombreFinal;

            if (move_uploaded_file($tmpName, $rutaFisica)) {
                // Generar la ruta relativa para la BD
                $rutaRelativa = '../../../docs/' . $referencia . '/' . $nombreFinal;

                // Registrar en BD
                $sqlArchivo = "INSERT INTO conta_referencias_archivos (Referencia_id, Nombre, Ruta, Origen) VALUES (?, ?, ?, 0)";
                $stmtArchivo = $con->prepare($sqlArchivo);
                $stmtArchivo->execute([$referencia, $nombreOriginal, $rutaRelativa]);

                $archivosGuardados[] = $nombreFinal;
            }
        }
    }
}



//---------------------Responder al Frontend-----------------------------
echo json_encode([
    'success' => true,
    'message' => 'Datos y archivos guardados correctamente.',
    'data' => [
        'iva' => $iva,
        'subtotal' => $subtotal,
        'importe' => $importe,
        'referencia' => $referencia,
        'archivos_guardados' => $archivosGuardados,
        'numero_poliza' => $numero_poliza
    ]
]);
exit;
