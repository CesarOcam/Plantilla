<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_consolidadora'], $_POST['nombre'])) {
    $id_consolidadora = (int) $_POST['id_consolidadora'];
    $nombre = $_POST['nombre'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE consolidadoras SET 
        denominacion_consolidadora = ?, fechaModificacion_consolidadora = ?, userModifico_consolidadora = ?
        WHERE id_consolidadora = ?";

    $params = [
        $nombre,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_consolidadora
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
