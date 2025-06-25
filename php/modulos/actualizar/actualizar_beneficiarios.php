<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_beneficiario'], $_POST['nombre'])) {
    $id_beneficiario = (int) $_POST['id_beneficiario'];
    $subcuentas = $_POST['subcuentas'] ?? [];
    $nombre = $_POST['nombre'];
    $nombreCorto = $_POST['nombre_corto'];
    $tipo = $_POST['tipo'];
    $rfc = $_POST['rfc'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual()
    {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE beneficiarios SET 
        Nombre = ?, NombreCorto = ?, Tipo = ?, Rfc = ?,  FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
        WHERE Id = ?";

    $params = [
        $nombre,
        $nombreCorto,
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

            $stmtDelete = $con->prepare("DELETE FROM subcuentas_beneficiarios WHERE beneficiario_id = ?");
            $stmtDelete->execute([$id_beneficiario]);


            // Insertar las subcuentas restantes en subcuentasbeneficiarios
            if (count($subcuentas) > 0) {
                $sqlSubcuentas = "INSERT INTO subcuentas_beneficiarios (beneficiario_id, subcuenta_id) VALUES (?, ?)";
                $stmtSub = $con->prepare($sqlSubcuentas);

                foreach ($subcuentas as $idSubcuenta) {
                    $stmtSub->execute([$id_beneficiario, $idSubcuenta]);
                }
            }

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