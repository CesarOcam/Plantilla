<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['nombre_recinto'], $_POST['aduana'], $_POST['curp'], $_POST['domicilio'], $_POST['fechaAcceso_transportista'])) {
    // Recoger todos los valores
    $nombre = $_POST['nombre_recinto'];
    $aduana = $_POST['aduana'];
    $curp = $_POST['curp'];
    $domicilio = $_POST['domicilio'];
    $fecha_acceso = $_POST['fechaAcceso_transportista'];

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }
    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;
    $exportadoCoi = 1;



    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO 2221_recintos 
    (
        nombre_recinto, aduana_recintos, curp_recintos, inmueble_recintos,fechaAcceso_transportista, fechaCreate_recintos, usuarioAlta_recintos, status_recintos
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)";

    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
    $nombre,
    $aduana,   // FechaAlta
    $curp,  // UsuarioAlta
    $domicilio, 
    $fecha_acceso,
    $fecha_alta,  // Activo
    $usuarioAlta,
    $activo,     // Nombre
    ];


    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

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

