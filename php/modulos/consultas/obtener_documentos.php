<?php
header('Content-Type: application/json');
require_once '../../conexion.php'; // Asegúrate de incluir tu conexión PDO

// Asumiendo que pasas la ID de la referencia por GET o sesión (modifica según tu lógica)
$referencia_id = $_GET['referencia_id'] ?? null;

if (!$referencia_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, ruta, tamano, extension FROM documentos WHERE referencia_id = ?");
    $stmt->execute([$referencia_id]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Añadir íconos según tipo de archivo
    foreach ($documentos as &$doc) {
        $ext = strtolower($doc['extension']);
        switch ($ext) {
            case 'pdf':
                $doc['icono'] = '<i class="bi bi-file-earmark-pdf"></i>';
                break;
            case 'zip':
            case 'rar':
                $doc['icono'] = '<i class="bi bi-file-zip"></i>';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
                $doc['icono'] = '<i class="bi bi-file-image"></i>';
                break;
            default:
                $doc['icono'] = '<i class="bi bi-file-earmark"></i>';
        }

        // Opciónal: convertir tamaño a formato legible
        $bytes = (int) $doc['tamano'];
        if ($bytes < 1024) {
            $doc['tamano'] = $bytes . ' B';
        } elseif ($bytes < 1048576) {
            $doc['tamano'] = round($bytes / 1024, 2) . ' KB';
        } else {
            $doc['tamano'] = round($bytes / 1048576, 2) . ' MB';
        }
    }

    echo json_encode($documentos);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener documentos: ' . $e->getMessage()]);
}
