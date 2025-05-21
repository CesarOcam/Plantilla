<?php
include_once(__DIR__ . '/../conexion.php');

// Leer JSON recibido
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['ids']) || !is_array($input['ids'])) {
    echo json_encode(['success' => false, 'msg' => 'No se recibieron IDs']);
    exit;
}

$ids = $input['ids'];

// Preparar la consulta con placeholders
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Cambia "statusEcomienda_exportador" o la columna correcta a 0 (desactivado)
$sql = "UPDATE navieras SET Activo = 0 WHERE Id IN ($placeholders)";
$stmt = $con->prepare($sql);

try {
    $stmt->execute($ids);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
