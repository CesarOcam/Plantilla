<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['nombre'], $_POST['rfc'], $_POST['tipo'])) {
    // Verifica si es una actualización (si trae ID)
    $id_exportador = isset($_POST['id_exportador']) ? (int) $_POST['id_exportador'] : null;

    // Recoger todos los valores (como ya tienes en tu código)
    $nombre = trim($_POST['nombre']);
    $curp = trim($_POST['curp']);
    $rfc = trim($_POST['rfc']);
    // ... el resto de variables igual que en tu código ...

    // Fecha y usuario alta, incluso puedes usar estos como fechaModificación si gustas
    $fecha_alta = date("Y-m-d H:i:s");
    $activo = 1;
    $usuarioAlta = 1;

    if ($id_exportador) {
        // 🛠 UPDATE
        $sql = "UPDATE 01clientes_exportadores SET 
            razonSocial_exportador = ?, curp_exportador = ?, rfc_exportador = ?, tipoClienteExportador = ?, 
            tipo_cliente = ?, nombreCorto_exportador = ?, calle_exportador = ?, noExt_exportador = ?, 
            noInt_exportador = ?, codigoPostal_exportador = ?, pagaCon_cliente = ?, colonia_exportador = ?, 
            localidad_exportador = ?, municipio_exportador = ?, idcat11_estado = ?, id2204clave_pais = ?, 
            contacto_cliente = ?, telefono_cliente = ?, emails_trafico = ?, logistico_asociado = ?, 
            status_exportador = ?, fechaAlta_exportador = ?, usuarioAlta_exportador = ?
        WHERE id_exportador = ?";

        $params = [
            $nombre, $curp, $rfc, $tipo_persona, $tipo_cliente, $nombre_conocido,
            $calle, $num_exterior, $num_interior, $cp, $quien_paga, $colonia, $localidad,
            $municipio, $estado, $pais, $contacto, $tel, $email_trafico, $logistico,
            $status, $fecha_alta, $usuarioAlta, $id_exportador
        ];
    } else {
        // 🆕 INSERT
        $sql = "INSERT IGNORE INTO 01clientes_exportadores 
        (
            razonSocial_exportador, curp_exportador, rfc_exportador, tipoClienteExportador, tipo_cliente,
            nombreCorto_exportador, calle_exportador, noExt_exportador, noInt_exportador, codigoPostal_exportador,
            pagaCon_cliente, colonia_exportador, localidad_exportador, municipio_exportador,
            idcat11_estado, id2204clave_pais, contacto_cliente, telefono_cliente, emails_trafico, logistico_asociado,
            status_exportador, fechaAlta_exportador, usuarioAlta_exportador
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $nombre, $curp, $rfc, $tipo_persona, $tipo_cliente, $nombre_conocido,
            $calle, $num_exterior, $num_interior, $cp, $quien_paga, $colonia, $localidad,
            $municipio, $estado, $pais, $contacto, $tel, $email_trafico, $logistico,
            $status, $fecha_alta, $usuarioAlta
        ];
    }

    // Verificar coincidencia de parámetros con los signos de ?
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params);

            if ($resultado) {
                echo $id_exportador ? "Cliente actualizado correctamente." : "Cliente guardado correctamente.";
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

