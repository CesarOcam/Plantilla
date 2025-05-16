<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['nombre'], $_POST['pais'])) {
    // Recoger todos los valores
    $nombre = trim($_POST['nombre']);
    $pais = trim($_POST['pais']);

  

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }

    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO buques 
    (
        Nombre, Activo, FechaAlta, UsuarioAlta, Pais
    )
    VALUES (?, ?, ?, ?, ?)";

    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
    $nombre,       // Nombre
    $activo,       // Activo
    $fecha_alta,   // FechaAlta
    $usuarioAlta,  // UsuarioAlta
    $pais          // Pais
    ];


    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Buque guardado correctamente.";
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

