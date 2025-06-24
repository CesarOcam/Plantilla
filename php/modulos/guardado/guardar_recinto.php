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

if (isset($_POST['nombre_recinto'], $_POST['aduana'], $_POST['curp'], $_POST['domicilio'], $_POST['fechaAcceso_transportista'])) {

    $nombre = $_POST['nombre_recinto'];
    $aduana = $_POST['aduana'];
    $curp = $_POST['curp'];
    $domicilio = $_POST['domicilio'];
    $fecha_acceso = $_POST['fechaAcceso_transportista'];

    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); 
    }
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];
    $exportadoCoi = 1;

    $sql = "INSERT INTO 2221_recintos 
    (
        inmueble_recintos, aduana_recintos,fechaAcceso_recintos, fechaCreate_recintos, usuarioAlta_recintos, status_recintos
    )
    VALUES (?, ?, ?, ?, ?, ?)";

    $params = [
    $nombre,
    $aduana,
    $fecha_acceso,
    $fecha_alta,
    $usuarioAlta,
    $activo,  
    ];

    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params);

            if ($resultado) {
                echo "Recinto guardado correctamente.";
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

