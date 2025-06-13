<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../../modulos/conexion.php'; // Asegúrate de incluir tu conexión PDO

try {
    if (isset($_POST['numeroReferencia'])) {
        $numero = $_POST['numeroReferencia'];

        $stmt = $con->prepare("SELECT Id FROM referencias WHERE Numero = ?");
        $stmt->execute([$numero]);
        $id = $stmt->fetchColumn();

        if ($id) {
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            // No encontró el registro
            echo json_encode(['success' => false, 'message' => "No existe referencia con Numero = $numero"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se recibió el parámetro numeroReferencia']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en BD: ' . $e->getMessage()]);
}
