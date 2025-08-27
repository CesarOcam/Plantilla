<?php
include('../conexion.php');

if (isset($_POST['id_cliente'], $_POST['nombre'], $_POST['rfc'], $_POST['tipo'])) {
    $id_cliente = (int) $_POST['id_cliente'];
    $nombre = trim($_POST['nombre']);
    $curp = trim($_POST['curp'] ?? '');
    $rfc = trim($_POST['rfc']);
    $nombre_factura = trim($_POST['nombre_factura'] ?? '');
    $rfc_factura = trim($_POST['rfc_factura']);
    $tipo_persona = $_POST['tipo'];
    $tipo_cliente = $_POST['tipo_cliente'] ?? null;
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
    $pais = $_POST['pais'] ?? null;
    $estado = $_POST['estado'] ?? null;
    $quien_paga = isset($_POST['pagaCon_cliente']) ? (int) $_POST['pagaCon_cliente'] : null;
    $logistico = $_POST['logistico_asociado'] ?? null;
    $status = isset($_POST['status_exportador']) ? (int) $_POST['status_exportador'] : null;
    $usuarioModificacion = 1;
    $fecha_modificacion = date("Y-m-d H:i:s");

    // Preparar SQL para actualización del cliente
    $sqlUpdate = "UPDATE 01clientes_exportadores SET 
        razonSocial_exportador = ?, curp_exportador = ?, rfc_exportador = ?, tipoClienteExportador = ?, tipo_cliente = ?,
        nombreCorto_exportador = ?, calle_exportador = ?, noExt_exportador = ?, noInt_exportador = ?, codigoPostal_exportador = ?,
        pagaCon_cliente = ?, colonia_exportador = ?, localidad_exportador = ?, municipio_exportador = ?,
        idcat11_estado = ?, id2204clave_pais = ?, contacto_cliente = ?, telefono_cliente = ?, logistico_asociado = ?,
        status_exportador = ?, fecha_ultimaActualizacionClientes = ?, usuarioModificar_exportador = ?, nombre_factura = ?, rfc_factura = ?
        WHERE id01clientes_exportadores = ?";

    $paramsUpdate = [
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
        $logistico,
        $status,
        $fecha_modificacion,
        $usuarioModificacion,
        $nombre_factura,
        $rfc_factura,
        $id_cliente,
    ];

    // Procesar correos recibidos
    $email_contabilidad = trim($_POST['emails_contabilidad'] ?? '');
    $correos_raw = explode(',', $email_contabilidad);
    $correos = [];
    foreach ($correos_raw as $correo) {
        $correo = trim($correo);
        if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $correos[] = $correo;
        }
    }
    // Eliminar duplicados por si acaso
    $correos = array_unique($correos);

    if (count($paramsUpdate) !== substr_count($sqlUpdate, '?')) {
        exit("Error: El número de parámetros no coincide con los tokens '?'");
    }

    try {
        $con->beginTransaction();

        // Actualizar cliente
        $stmtUpdate = $con->prepare($sqlUpdate);
        if (!$stmtUpdate->execute($paramsUpdate)) {
            throw new Exception("Error al actualizar cliente: " . implode(", ", $stmtUpdate->errorInfo()));
        }

        // Obtener correos existentes en BD para el cliente y tipo 3
        $sqlSelect = "SELECT correo FROM correos_01clientes_exportadores WHERE id01clientes_exportadores = ? AND tipo_correo = 3";
        $stmtSelect = $con->prepare($sqlSelect);
        $stmtSelect->execute([$id_cliente]);
        $correos_en_bd = $stmtSelect->fetchAll(PDO::FETCH_COLUMN);

        // Calcular correos a eliminar (están en BD pero ya no en input)
        $correos_a_eliminar = array_diff($correos_en_bd, $correos);

        // Calcular correos a insertar (están en input pero no en BD)
        $correos_a_insertar = array_diff($correos, $correos_en_bd);

        // Eliminar correos obsoletos
        if (!empty($correos_a_eliminar)) {
            $placeholders = implode(',', array_fill(0, count($correos_a_eliminar), '?'));
            $sqlDelete = "DELETE FROM correos_01clientes_exportadores WHERE id01clientes_exportadores = ? AND tipo_correo = 3 AND correo IN ($placeholders)";
            $stmtDelete = $con->prepare($sqlDelete);
            $stmtDelete->execute(array_merge([$id_cliente], $correos_a_eliminar));
        }

        // Insertar correos nuevos
        if (!empty($correos_a_insertar)) {
            $sqlInsert = "INSERT INTO correos_01clientes_exportadores (id01clientes_exportadores, correo, tipo_correo) VALUES (?, ?, 3)";
            $stmtInsert = $con->prepare($sqlInsert);
            foreach ($correos_a_insertar as $correoValido) {
                $stmtInsert->execute([$id_cliente, $correoValido]);
            }
        }

        $con->commit();
        echo "ok";

    } catch (Exception $e) {
        $con->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Faltan datos obligatorios.";
}
?>
