<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['nombre_transportista'], $_POST['rfc_transportista'], $_POST['curp_transportista'])) {
    // Recoger todos los valores
    $nombre = $_POST['nombre_transportista'];
    $rfc = $_POST['rfc_transportista'];
    $curp = $_POST['curp_transportista'];
    $domicilio = $_POST['domicilio_fiscal_transportista'];
    $fechaOriginal = $_POST['fechaAcceso_transportista']; // Por ejemplo: "2025-05-17T14:30"
    $fecha = new DateTime($fechaOriginal);
    $fechaAcceso = $fecha->format("Y-m-d H:i:s");
  
    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }

    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;
    $empresa = 2;

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT IGNORE INTO transportista 
    (
        nombre_transportista, rfc_transportista, curp_transportista, domicilio_fiscal_transportista, fechaAcceso_transportista, status_transportista, userCreate_transportista, created_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";


    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
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

    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

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

