<?php
// eliminar_archivo.php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ruta'])) {
    echo json_encode(['success' => false, 'message' => 'Ruta no especificada']);
    exit;
}

$ruta = $data['ruta'];

// Aquí debes sanitizar y validar $ruta para evitar borrado de archivos que no corresponden

// Ejemplo básico: solo borrar si existe el archivo y está en una carpeta específica
$basePath = __DIR__ . '/uploads/';  // carpeta donde guardas archivos
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

// Además, elimina el registro en la base de datos (si aplica)
try {
    include_once(__DIR__ . '/../conexion.php');

    $stmt = $con->prepare("DELETE FROM referencias_archivos WHERE Ruta = :ruta");
    $stmt->bindParam(':ruta', $ruta);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
