<?php
session_start();
include_once(__DIR__ . '/../conexion.php');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$facturaId = $input['id'] ?? null;

if (!$facturaId || !is_numeric($facturaId)) {
    echo json_encode(['success' => false, 'message' => 'ID de factura no válido']);
    exit;
}

try {
    $stmt = $con->prepare("DELETE FROM facturas_registradas WHERE Id = ?");
    $stmt->execute([$facturaId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada o ya eliminada']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}