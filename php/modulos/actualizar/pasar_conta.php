<?php
session_start();
include('../conexion.php'); // ajusta la ruta según tu proyecto

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = (int) $_POST['id'];

$sql = "UPDATE referencias SET Status = 2 WHERE id = ?";

$stmt = $con->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
    exit;
}

if ($stmt->execute([$id])) {
    echo json_encode(['success' => true, 'message' => 'El registro fue actualizado a contabilidad']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el registro']);
}
