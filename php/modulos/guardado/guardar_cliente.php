<?php
include('../conexion.php');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['nombre'], $_POST['rfc'], $_POST['tipo'])) {
    // Recoger todos los valores
    $nombre = trim($_POST['nombre']);
    $curp = trim($_POST['curp']);
    $rfc = trim($_POST['rfc']);
    $tipo_persona = $_POST['persona']; //tipoClienteExportador
    $tipo_cliente = $_POST['tipo']; //tipo_cliente
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
    $logistico = $_POST['logistico_asociado'];
    $email_trafico = trim($_POST['emails_trafico'] ?? '');
    $status = isset($_POST['status_exportador']) ? (int) $_POST['status_exportador'] : null;
  

    // Función para obtener la fecha y hora actual
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
    }

    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT IGNORE INTO 01clientes_exportadores 
    (
        razonSocial_exportador, curp_exportador, rfc_exportador, tipoClienteExportador, tipo_cliente,
        nombreCorto_exportador, calle_exportador, noExt_exportador, noInt_exportador, codigoPostal_exportador,
        pagaCon_cliente, colonia_exportador, localidad_exportador, municipio_exportador,
        idcat11_estado, id2204clave_pais, contacto_cliente, telefono_cliente, emails_trafico, logistico_asociado,
        status_exportador, fechaAlta_exportador, usuarioAlta_exportador
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


    // Crear el array de parámetros, sin incluir el valor de 'Activo' ya que ya está seteo como 1
    $params = [
        $nombre,         // razonSocial_exportador
        $curp,        
        $rfc,            // rfc_exportador
        $tipo_persona,   // tipoClienteExportador
        $tipo_cliente,
        $nombre_conocido,   // tipo_cliente
        $calle,          // calle_exportador
        $num_exterior,   // noExt_exportador
        $num_interior,   // noInt_exportador
        $cp,             // codigoPostal_exportador
        $quien_paga,
        $colonia,        // colonia_exportador
        $localidad,      // localidad_exportador
        $municipio,      // municipio_exportador
        $estado,         // idcat11_estado
        $pais,           // id2204clave_pais
        $contacto,
        $tel,            // telefono_cliente
        $email_trafico,  // emails_trafico
        $logistico,
        $status,         // status_exportador
        $fecha_alta,     // FechaAlta
        $usuarioAlta     // UsuarioAlta
    ];

    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            if ($resultado) {
                echo "Cliente guardado correctamente.";
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

