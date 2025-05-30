<?php
include_once(__DIR__ . '/../conexion.php');
header('Content-Type: application/json');

// Leer y decodificar el cuerpo JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó ID']);
    exit;
}

$id = intval($data['id']);

try {
    $stmt = $con->prepare("UPDATE solicitudes SET Status = 0 WHERE Id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
