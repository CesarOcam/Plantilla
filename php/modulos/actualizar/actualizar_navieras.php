<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['idtransporte'], $_POST['identificacion'])) {
    $idtransporte = (int) $_POST['idtransporte'];
    $identificacion = $_POST['identificacion'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE transporte SET 
        identificacion = ?, userModificacion_transporte = ?, fechaModificacion_transporte = ?
        WHERE idtransporte = ?";

    $params = [
        $identificacion,
        $usuarioModificacion,
        $fecha_modificacion,
        $idtransporte
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
