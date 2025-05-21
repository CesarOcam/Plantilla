<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_cuenta'], $_POST['numero'], $_POST['nombre'], $_POST['tipo'])) {
    $id_cuenta = (int) $_POST['id_cuenta'];
    $numero = $_POST['numero'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE cuentas SET 
        Numero = ?, Nombre = ?, TipoSaldo = ?,  FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
        WHERE Id = ?";

    $params = [
        $numero,
        $nombre,
        $tipo,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_cuenta
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
