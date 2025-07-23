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

if (isset($_POST['nombre_transportista'], $_POST['rfc_transportista'], $_POST['curp_transportista'])) {
    $nombre = $_POST['nombre_transportista'];
    $rfc = $_POST['rfc_transportista'];
    $curp = $_POST['curp_transportista'];
    $domicilio = $_POST['domicilio_fiscal_transportista'];
    $fechaOriginal = $_POST['fechaAcceso_transportista'];
    $fecha = new DateTime($fechaOriginal);
    $fechaAcceso = $fecha->format("Y-m-d H:i:s");
  
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); 
    }

    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];
    $empresa = 2;

    $sql = "INSERT INTO transportista 
    (
        nombre_transportista, rfc_transportista, curp_transportista, domicilio_fiscal_transportista, fechaAcceso_transportista, status_transportista, userCreate_transportista, created_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $nombre,
        $rfc,
        $curp,   
        $domicilio,      
        $fechaAcceso,     
        $activo,    
        $usuarioAlta,
        $fecha_alta     
    ];

    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); 

            if ($resultado) {
                echo "Naviera guardada correctamente.";
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

