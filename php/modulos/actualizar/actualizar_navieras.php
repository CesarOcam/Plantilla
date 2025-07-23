<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['idtransportista'], $_POST['nombre_transportista'])) {
    $idtransportista = (int) $_POST['idtransportista'];
    $nombre_transportista = $_POST['nombre_transportista'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE transportista SET 
        nombre_transportista = ?, userModificacion_transportista = ?, fechaModificacion_transportista = ?
        WHERE idtransportista = ?";

    $params = [
        $nombre_transportista,
        $usuarioModificacion,
        $fecha_modificacion,
        $idtransportista
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
