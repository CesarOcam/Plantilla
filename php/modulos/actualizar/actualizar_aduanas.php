<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset(
    $_POST['id_aduana'],
    $_POST['nombre'],
    $_POST['clave'],
    $_POST['seccion'],
    $_POST['denominacion'],
    $_POST['prefijo'],
    $_POST['tipo'],
    $_POST['subcuenta_log'],
    $_POST['subcuenta_ab_log'],
    $_POST['sub_cargo_log'],
    $_POST['subcuenta_exp'],
    $_POST['subcuenta_ab_exp'],
    $_POST['sub_cargo_exp'])) {

    $id_aduana = $_POST['id_aduana'];
    $nombre = $_POST['nombre'];
    $clave =$_POST['clave'];
    $seccion =$_POST['seccion'];
    $denominacion =$_POST['denominacion'];
    $prefijo =$_POST['prefijo'];
    $tipo =$_POST['tipo'];
    $sub_log =$_POST['subcuenta_log'];
    $sub_ab_log =$_POST['subcuenta_ab_log'];
    $sub_cargo_log =$_POST['sub_cargo_log'];
    $sub_exp =$_POST['subcuenta_exp'];
    $sub_ab_exp =$_POST['subcuenta_ab_exp'];
    $sub_argo_exp =$_POST['sub_cargo_exp'];

    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE 2201aduanas SET 
        nombre_corto_aduana = ?, aduana_aduana = ?,  seccion_aduana = ?, denominacion_aduana = ?, prefix_aduana = ?, tipoAduana = ?,
        SubcuentaClientesLogId = ?, SubcuentaCuotasAbonoLogId = ?, SubcuentaCuotasCargoLogId = ?, SubcuentaClientesExpId = ?, SubcuentaCuotasAbonoExpId = ?, SubcuentaCuotasCargoLogId = ?,
        updated_at = ?, usuarioAlta_Modificacion = ?
        WHERE id2201aduanas = ?";

    $params = [
        $nombre,
        $clave,
        $seccion,
        $denominacion,
        $prefijo,
        $tipo,
        $sub_log,
        $sub_ab_log,
        $sub_cargo_log,
        $sub_exp,
        $sub_ab_exp,
        $sub_argo_exp,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_aduana
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
