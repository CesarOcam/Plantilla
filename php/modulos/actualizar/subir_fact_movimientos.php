<?php
include('../conexion.php'); // conexión PDO
header('Content-Type: application/json; charset=utf-8');

$referencia_id = $_POST['ReferenciaId'] ?? null;
$partida_id = $_POST['PartidaId'] ?? null;
$uuid = $_POST['UUID'] ?? null;
$serie = $_POST['Serie'] ?? null;
$folio = $_POST['Folio'] ?? null;
$origen = $_POST['Origen'] ?? null;
$nombre_comentario = $serie.$folio;

if (!$uuid) {
    echo json_encode(['ok' => false, 'msg' => 'No se recibió UUID']);
    exit;
}

if (!$referencia_id || !$partida_id) {
    echo json_encode(['ok' => false, 'msg' => 'Faltan IDs necesarios']);
    exit;
}

$archivos = $_FILES['archivo'] ?? null;
$total = (isset($archivos['name']) && is_array($archivos['name'])) ? count($archivos['name']) : 0;

if ($total !== 2) {
    echo json_encode(['ok' => false, 'msg' => 'Debes subir exactamente 2 archivos: PDF y XML']);
    exit;
}

// Separar PDF y XML
$pdf = $xml = null;
for ($i = 0; $i < $total; $i++) {
    $name = $archivos['name'][$i];
    if (preg_match('/\.pdf$/i', $name)) $pdf = $i;
    if (preg_match('/\.xml$/i', $name)) $xml = $i;
}

if ($pdf === null || $xml === null) {
    echo json_encode(['ok' => false, 'msg' => 'Faltan el PDF o el XML']);
    exit;
}

// Verificar que tengan mismo nombre base
$pdfBase = preg_replace('/\.pdf$/i', '', $archivos['name'][$pdf]);
$xmlBase = preg_replace('/\.xml$/i', '', $archivos['name'][$xml]);

if ($pdfBase !== $xmlBase) {
    echo json_encode(['ok' => false, 'msg' => 'PDF y XML deben tener el mismo nombre base']);
    exit;
}

// --- VALIDACIONES ANTES DE SUBIR ---

// 1. Revisar si ya existe el par en conta_referencias_archivos
$stmtCheck = $con->prepare("SELECT COUNT(*) FROM conta_referencias_archivos WHERE Referencia_id = ? AND Nombre LIKE ?");
$stmtCheck->execute([$referencia_id, $pdfBase . '%']);
$countFiles = $stmtCheck->fetchColumn();
if ($countFiles >= 2) {
    // Obtener el número de referencia para mostrar en el Swal
    $stmtRef = $con->prepare("SELECT Numero FROM conta_referencias WHERE Id = ?");
    $stmtRef->execute([$referencia_id]);
    $numeroReferencia = $stmtRef->fetchColumn();

    echo json_encode([
        'ok' => false,
        'msg' => 'Ya existe un par de archivos con ese nombre',
        'referencia' => $numeroReferencia,
        'nombreArchivo' => $nombreSinExtension,
    ]);
    exit;
}

// 2. Revisar si el UUID ya existe en conta_facturas_registradas
$stmtUUID = $con->prepare("
    SELECT f.UUID, r.Numero 
    FROM conta_facturas_registradas f
    INNER JOIN conta_referencias r ON r.Id = f.Referencia_id
    WHERE f.UUID = ?
");
$stmtUUID->execute([$uuid]);
$uuidExistente = $stmtUUID->fetch(PDO::FETCH_ASSOC);
if ($uuidExistente) {
    echo json_encode([
        'ok' => false,
        'msg' => 'El UUID ya existe en otra referencia',
        'uuid' => $uuidExistente['UUID'],
        'referencia' => $uuidExistente['Numero']
    ]);
    exit;
}

// --- SUBIDA DE ARCHIVOS ---
$uploadBaseDir = '../../../docs/';
$uploadDir = $uploadBaseDir . $referencia_id . '/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

try {
    $con->beginTransaction();
    for ($i = 0; $i < $total; $i++) {
        if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
            $nombreOriginal = basename($archivos['name'][$i]);
            $nombreFinal = $nombreOriginal;   // usar tal cual
            $rutaFinal = $uploadDir . $nombreFinal;

            if (move_uploaded_file($archivos['tmp_name'][$i], $rutaFinal)) {
                $sqlArchivo = "INSERT INTO conta_referencias_archivos (Referencia_id, Partida_id, Nombre, Ruta, UUID, Origen) VALUES (?, ?, ?, ?, ?, ?)";
                $stmtArchivo = $con->prepare($sqlArchivo);
                $stmtArchivo->execute([$referencia_id, $partida_id, $nombreFinal, $rutaFinal, $uuid, $origen]);

                // Actualizar observaciones en la partida
                $nombreSinExtension = pathinfo($nombre_comentario, PATHINFO_FILENAME);
                $sqlActualizarPartida = "UPDATE conta_partidaspolizas SET Observaciones = ? WHERE Partida = ?";
                $stmtActualizar = $con->prepare($sqlActualizarPartida);
                $stmtActualizar->execute([$nombreSinExtension, $partida_id]);
            }
        }
    }
    $con->commit();
    echo json_encode([
    'ok' => true,
    'msg' => 'Archivos subidos correctamente',
    'partidaId' => $partida_id,
    'nombreArchivo' => $pdfBase  // o el nombre que quieras mostrar en observaciones
]);
} catch (PDOException $e) {
    $con->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'Error al subir archivos: ' . $e->getMessage()]);
}
