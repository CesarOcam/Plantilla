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
$usuarioCancelo = (int) $_SESSION['usuario_id'];

// Empezar transacción para asegurar integridad
$con->beginTransaction();

try {
    // Cancelar póliza
    $sql = "UPDATE conta_polizas SET Activo=0, FechaCancelo=NOW() WHERE id = ?";
    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta de póliza');
    }
    if (!$stmt->execute([$id])) {
        throw new Exception('Error al cancelar la póliza');
    }

    // Actualizar partidas de la póliza a Activo = 2 y setear UsuarioCancelo
    $sqlPartidas = "UPDATE conta_partidaspolizas SET Activo = 0, UsuarioCancelo = ? WHERE PolizaId = ?";
    $stmtPartidas = $con->prepare($sqlPartidas);
    if (!$stmtPartidas) {
        throw new Exception('Error al preparar la consulta de partidas');
    }
    if (!$stmtPartidas->execute([$usuarioCancelo, $id])) {
        throw new Exception('Error al actualizar partidas');
    }

    // Confirmar transacción
    $con->commit();

    echo json_encode(['success' => true, 'message' => 'La póliza y sus partidas fueron canceladas correctamente']);

} catch (Exception $e) {
    // Revertir cambios si hay error
    $con->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
