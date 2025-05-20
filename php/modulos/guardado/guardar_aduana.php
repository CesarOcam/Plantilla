<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset(
    $_POST['nombre_corto_aduana'], 
    $_POST['aduana_aduana'], 
    $_POST['seccion_aduana'], 
    $_POST['denominacion_aduana'], 
    $_POST['prefix_aduana'], 
    $_POST['tipoAduana']
    )) {
    // Recoger todos los valores
    $nombre = $_POST['nombre_corto_aduana'];
    $clave = $_POST['aduana_aduana'];
    $seccion = $_POST['seccion_aduana'];
    $denominacion = $_POST['denominacion_aduana'];
    $prefijo = $_POST['prefix_aduana'];

    $tipo = $_POST['tipoAduana'];

    $sub_cli_log = $_POST['SubcuentaClientesLogId'];
    $sub_cli_exp = $_POST['SubcuentaClientesExpId'];
    $sub_abono_log = $_POST['SubcuentaCuotasAbonoLogId'];
    $sub_abono_exp = $_POST['SubcuentaCuotasAbonoExpId'];
    $sub_cargo_log = $_POST['SubcuentaCuotasCargoLogId'];
    $sub_cargo_exp = $_POST['SubcuentaCuotasCargoExpId'];
    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }
    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO 2201aduanas 
    (
        nombre_corto_aduana, aduana_aduana, seccion_aduana, denominacion_aduana, prefix_aduana,
        tipoAduana,
        SubcuentaClientesLogId, SubcuentaClientesExpId, SubcuentaCuotasAbonoLogId, SubcuentaCuotasAbonoExpId, SubcuentaCuotasCargoLogId, SubcuentaCuotasCargoExpId,
        fechaCreate_aduana, usuarioAlta_aduana, status_aduana
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
    $nombre,
    $clave,
    $seccion,
    $denominacion,
    $prefijo,

    $tipo,

    $sub_cli_log,
    $sub_cli_exp,
    $sub_abono_log,
    $sub_abono_exp,
    $sub_cargo_log,
    $sub_cargo_exp,

    $fecha_alta,
    $usuarioAlta,
    $activo
    ];


    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Aduana guardada correctamente.";
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

