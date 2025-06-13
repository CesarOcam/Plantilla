<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_recinto'], $_POST['aduana'], $_POST['recinto'])) {
    $id_recinto = (int) $_POST['id_recinto'];
    $aduana = $_POST['aduana'];
    $recinto = $_POST['recinto'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE 2221_recintos SET 
        aduana_recintos = ?, inmueble_recintos = ?, updated_at = ?, usuarioModificacion_recintos = ?
        WHERE id2221_recintos = ?";

    $params = [
        $aduana,
        $recinto,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_recinto
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
