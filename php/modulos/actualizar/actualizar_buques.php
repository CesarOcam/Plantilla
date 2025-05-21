<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_buque'], $_POST['nombre'])) {
    $id_buque = (int) $_POST['id_buque'];
    $nombre = trim($_POST['nombre']);
    $pais = isset($_POST['pais']) ? (int) $_POST['pais'] : null;


    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE buques SET 
        Nombre = ?, Pais = ?, FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
        WHERE Id = ?";

    $params = [
        $nombre,
        $pais,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_buque
    ];

    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con los tokens '?'";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params);

            if ($resultado) {
                echo "ok";
            } else {
                echo "Error al actualizar: " . implode(", ", $stmt->errorInfo());
            }
        } else {
            echo "Error al preparar consulta: " . implode(", ", $con->errorInfo());
        }
    }
} else {
    echo "Faltan datos obligatorios.";
}
?>
