<?php
session_start(); 
include('../conexion.php');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

if (isset($_POST['denominacion_consolidadora'])) {
    // Recoger todos los valores
    $nombre = trim($_POST['denominacion_consolidadora']);
 
    function obtenerFechaHoraActual() {
        date_default_timezone_set("America/Mexico_City");
        return date("Y-m-d H:i:s"); 
    }

    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];

    $sql = "INSERT INTO consolidadoras 
    (
        fechaCreate_consolidadora, userCreate_consolidadora, status_consolidadora, denominacion_consolidadora
    )
    VALUES (?, ?, ?, ?)";

    $params = [
    $fecha_alta, 
    $usuarioAlta,
    $activo,
    $nombre,
    ];

    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Consolidadora guardada correctamente.";
            } else {
                echo "Error al guardar: " . implode(", ", $stmt->errorInfo());
            }
        } else {
            echo "Error al preparar la consulta: " . implode(", ", $con->errorInfo());
        }
    }
} else {
    echo "Faltan datos obligatorios.";
}
?>

