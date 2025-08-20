<?php
// download.php
$baseDir = __DIR__ . '/docs/'; // carpeta raíz de tus archivos

if (!isset($_GET['file'])) {
    die('Archivo no especificado.');
}

$archivo = $_GET['file'];

// Evitar traversal (../)
$archivo = str_replace(['../','..\\'], '', $archivo);

$rutaCompleta = realpath($baseDir . $archivo);

// Validar que el archivo exista y esté dentro de docs
if (!$rutaCompleta || strpos($rutaCompleta, realpath($baseDir)) !== 0 || !file_exists($rutaCompleta)) {
    die('Archivo no disponible.');
}

// Forzar descarga
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($rutaCompleta) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($rutaCompleta));

readfile($rutaCompleta);
exit;
