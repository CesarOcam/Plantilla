<?php
session_start();
include('conexion.php');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['usuario'], $input['clave'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$usuario = $input['usuario'];
$clave = $input['clave'];

$sql = "SELECT Id, Nombre, Hash FROM usuarios WHERE Nombre = :usuario LIMIT 1";
$stmt = $con->prepare($sql);
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit;
}

if (password_verify($clave, $user['Hash'])) {
    $_SESSION['usuario_id'] = $user['Id'];
    $_SESSION['usuario_nombre'] = $user['Nombre'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
}
