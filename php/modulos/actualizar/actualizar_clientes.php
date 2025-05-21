<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_cliente'], $_POST['nombre'], $_POST['rfc'], $_POST['tipo'])) {
    $id_cliente = (int) $_POST['id_cliente'];
    $nombre = trim($_POST['nombre']);
    $curp = trim($_POST['curp']);
    $rfc = trim($_POST['rfc']);
    $tipo_persona = $_POST['tipo'];
    $tipo_cliente = $_POST['tipo_cliente'];
    $nombre_conocido = trim($_POST['nombre_corto'] ?? '');
    $contacto = trim($_POST['contacto_cliente'] ?? '');
    $tel = trim($_POST['telefono_cliente'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $num_exterior = $_POST['num_exterior'] ?? '';
    $num_interior = $_POST['num_interior'] ?? '';
    $cp = $_POST['cp'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $municipio = trim($_POST['municipio'] ?? '');
    $pais = $_POST['pais'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $quien_paga = isset($_POST['pagaCon_cliente']) ? (int) $_POST['pagaCon_cliente'] : null;
    $logistico = isset($_POST['logistico_asociado']) ? $_POST['logistico_asociado'] : null;
    $email_trafico = trim($_POST['emails_trafico'] ?? '');
    $status = isset($_POST['status_exportador']) ? (int) $_POST['status_exportador'] : null;
    $usuarioModificacion = 1;
    // Fecha de modificación
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }
    $fecha_modificacion = obtenerFechaHoraActual();

    // Consulta UPDATE
    $sql = "UPDATE 01clientes_exportadores SET 
        razonSocial_exportador = ?, curp_exportador = ?, rfc_exportador = ?, tipoClienteExportador = ?, tipo_cliente = ?,
        nombreCorto_exportador = ?, calle_exportador = ?, noExt_exportador = ?, noInt_exportador = ?, codigoPostal_exportador = ?,
        pagaCon_cliente = ?, colonia_exportador = ?, localidad_exportador = ?, municipio_exportador = ?,
        idcat11_estado = ?, id2204clave_pais = ?, contacto_cliente = ?, telefono_cliente = ?, emails_trafico = ?, logistico_asociado = ?,
        status_exportador = ?, fecha_ultimaActualizacionClientes = ?, usuarioModificar_exportador = ?
        WHERE id01clientes_exportadores = ?";

    $params = [
        $nombre,
        $curp,
        $rfc,
        $tipo_persona,
        $tipo_cliente,
        $nombre_conocido,
        $calle,
        $num_exterior,
        $num_interior,
        $cp,
        $quien_paga,
        $colonia,
        $localidad,
        $municipio,
        $estado,
        $pais,
        $contacto,
        $tel,
        $email_trafico,
        $logistico,
        $status,
        $fecha_modificacion,
        $usuarioModificacion,
        $id_cliente // Al final va el ID para el WHERE
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
