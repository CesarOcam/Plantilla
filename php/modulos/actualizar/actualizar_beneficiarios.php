<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_beneficiario'], $_POST['nombre'])) {
    $id_beneficiario = (int) $_POST['id_beneficiario'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $rfc = $_POST['rfc'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE beneficiarios SET 
        Nombre = ?, Tipo = ?, Rfc = ?,  FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
        WHERE Id = ?";

    $params = [
        $nombre,
        $tipo,
        $rfc,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_beneficiario
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
