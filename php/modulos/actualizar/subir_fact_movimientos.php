<?php
include('../conexion.php'); // tu conexión PDO

$referencia_id = $_POST['ReferenciaId'] ?? null;
$partida_id = $_POST['PartidaId'] ?? null;

if (!$referencia_id || !$partida_id) {
    echo json_encode(['ok' => false, 'msg' => 'Faltan IDs necesarios']);
    exit;
}

$uploadBaseDir = '../../../docs/';
$uploadDir = $uploadBaseDir . $referencia_id . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$archivos = $_FILES['archivo'] ?? null;
$total = (is_array($archivos['name']) && is_array($archivos['name'])) ? count($archivos['name']) : 0;

if ($total !== 2) {
    echo json_encode(['ok' => false, 'msg' => 'Debes subir exactamente 2 archivos: PDF y XML']);
    exit;
}

$pdf = $xml = null;

// Separar PDF y XML
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

// Subir ambos archivos e insertar en DB
try {
    $con->beginTransaction();
    for ($i = 0; $i < $total; $i++) {
        if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
            $nombreOriginal = basename($archivos['name'][$i]);
            $nombreFinal = uniqid() . "_" . $nombreOriginal;
            $rutaFinal = $uploadDir . $nombreFinal;

            if (move_uploaded_file($archivos['tmp_name'][$i], $rutaFinal)) {
                $sqlArchivo = "INSERT INTO conta_referencias_archivos (Referencia_id, Partida_id, Nombre, Ruta) VALUES (?, ?, ?, ?)";
                $stmtArchivo = $con->prepare($sqlArchivo);
                $stmtArchivo->execute([$referencia_id, $partida_id, $nombreOriginal, $rutaFinal]);
            }
        }
    }
    $con->commit();
    echo json_encode(['ok' => true, 'msg' => 'Archivos subidos correctamente']);
} catch (PDOException $e) {
    $con->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'Error al subir archivos: ' . $e->getMessage()]);
}
