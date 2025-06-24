<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ruta'])) {
    echo json_encode(['success' => false, 'message' => 'Ruta no especificada']);
    exit;
}
$ruta = $data['ruta'];

$basePath = __DIR__ . '/uploads/';
$rutaCompleta = realpath($ruta);

if (!$rutaCompleta || strpos($rutaCompleta, realpath($basePath)) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Ruta inválida']);
    exit;
}

if (file_exists($rutaCompleta)) {
    if (!unlink($rutaCompleta)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo borrar el archivo']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    exit;
}

try {
    include_once(__DIR__ . '/../conexion.php');

    $stmt = $con->prepare("DELETE FROM referencias_archivos WHERE Ruta = :ruta");
    $stmt->bindParam(':ruta', $ruta);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
