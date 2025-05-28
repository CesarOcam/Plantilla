<?php
include('../conexion.php');
header('Content-Type: application/json');

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['aduana'], $_POST['exportador'], $_POST['logistico'])) {
    // Recoger todos los valores
    function obtenerFechaHoraActual()
    {
        return date("Y-m-d H:i:s");
    }

    function parseFecha($fecha)
    {
        return !empty($fecha) ? date("Y-m-d H:i:s", strtotime($fecha)) : null;
    }

    function parseHora($hora)
    {
        return !empty($hora) ? date("H:i:s", strtotime($hora)) : null;
    }

    $aduana = trim($_POST['aduana']) ?: null;
    $exportador = trim($_POST['exportador']) ?: null;
    $logistico = trim($_POST['logistico']) ?: null;
    $mercancia = $_POST['mercancia'] ?: null;
    $marcas = $_POST['marcas'] ?: null;
    $pedimento = $_POST['pedimento'] ?: null;
    $clave_ped = $_POST['clave_pedimento'] ?: null;
    $peso = $_POST['peso'] ?: null;
    $cantidad = $_POST['cantidad'] ?: null;
    $bultos = $_POST['bultos'] ?: null;
    $contenedor = $_POST['contenedor'] ?: null;
    $consolidadora = $_POST['consolidadora'] ?: null;
    $resultado_mod = $_POST['resultado_mod'] ?: null;
    $recinto = $_POST['recinto'] ?: null;
    $naviera = $_POST['naviera'] ?: null;
    $cierre_doc = parseFecha($_POST['cierre_doc'] ?? null);
    $fecha_pago = parseFecha($_POST['fecha_pago'] ?? null);
    $buque = $_POST['buque'] ?: null;
    $booking = $_POST['booking'] ?: null;
    $cierre_desp = parseFecha($_POST['cierre_desp'] ?? null);
    $hora_desp = parseHora($_POST['hora_desp'] ?? null);
    $viaje = $_POST['viaje'] ?: null;
    $su_referencia = $_POST['su_referencia'] ?: null;
    $fecha_doc = parseFecha($_POST['fecha_doc'] ?? null);
    $fecha_eta = parseFecha($_POST['fecha_eta'] ?? null);
    $puerto_dec = $_POST['puerto_desc'] ?: null;
    $puerto_dest = $_POST['puerto_dest'] ?: null;
    $comentarios = $_POST['comentarios'] ?: null;


    // Obtener la fecha y hora actual
    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $numero = '';
    $usuarioAlta = 1;

    //Se crea el número de referencia
    $sqlAduana = "SELECT nombre_corto_aduana FROM 2201aduanas WHERE id2201aduanas = ?";
    $stmtAduana = $con->prepare($sqlAduana);
    $stmtAduana->execute([$aduana]);
    $nombreCorto = $stmtAduana->fetchColumn();

    if ($nombreCorto) {
        $letra = strtoupper(substr(trim($nombreCorto), 0, 1));
        $anioDigito = date('Y') % 10;
        $prefijo = $letra . $anioDigito; // Ej: F5

        // Buscar último número con ese prefijo
        $sqlUltimoNumero = "
        SELECT Numero 
        FROM referencias 
        WHERE Numero LIKE :prefijo 
        ORDER BY CAST(SUBSTRING(Numero, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ";
        $stmtUltimo = $con->prepare($sqlUltimoNumero);
        $stmtUltimo->execute(['prefijo' => "$prefijo%"]);
        $ultimoNumero = $stmtUltimo->fetchColumn();

        if ($ultimoNumero) {
            $num = intval(substr($ultimoNumero, 2)) + 1;
            $numero = $prefijo . str_pad($num % 10000, 4, "0", STR_PAD_LEFT);
        } else {
            $numero = $prefijo . "0000";
        }
    } else {
        die("No se encontró nombre corto para la aduana seleccionada.");
    }

    // Asegurarse de que todos los campos coincidan con los de la base de datos
    $sql = "INSERT INTO referencias 
    (
        AduanaId, Numero, ClienteExportadorId, ClienteLogisticoId, Mercancia, Marcas,
        Pedimentos, ClavePedimento, PesoBruto, Cantidad, Bultos,
        Contenedor, ConsolidadoraId, ResultadoModulacion, RecintoId,
        NavieraId, CierreDocumentos, FechaPago, BuqueId, Booking, CierreDespacho,
        HoraDespacho, Viaje, SuReferencia, FechaDocumentado, LlegadaEstimada, PuertoDescarga, PuertoDestino, Comentarios,
        FechaAlta, Status, UsuarioAlta
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $aduana,
        $numero,
        $exportador,
        $logistico,
        $mercancia,
        $marcas,
        $pedimento,
        $clave_ped,
        $peso,
        $cantidad,
        $bultos,
        $contenedor,
        $consolidadora,
        $resultado_mod,
        $recinto,
        $naviera,
        $cierre_doc,
        $fecha_pago,
        $buque,
        $booking,
        $cierre_desp,
        $hora_desp,
        $viaje,
        $su_referencia,
        $fecha_doc,
        $fecha_eta,
        $puerto_dec,
        $puerto_dest,
        $comentarios,
        $fecha_alta,
        $activo,
        $usuarioAlta
    ];

    // Verificar que el número de parámetros coincida con el número de `?` en la consulta
    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        try {
            $con->beginTransaction(); // Inicia transacción

            $stmt = $con->prepare($sql);
            if ($stmt) {
                $resultado = $stmt->execute($params);

                if ($resultado) {
                    // Obtener el ID insertado
                    $referencia_id = $con->lastInsertId();

                    // Subida de archivos
                    $uploadDir = '../../../docs/';
                    // Crear carpeta si no existe
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    // $_FILES para los archivos subidos
                    $archivos = $_FILES['documentos'] ?? null;

                    if (is_array($archivos) && isset($archivos['name']) && is_array($archivos['name'])) {
                        $total = count($archivos['name']);
                    } else {
                        $total = 0;
                    }



                    for ($i = 0; $i < $total; $i++) {
                        if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                            $nombreOriginal = basename($archivos['name'][$i]);
                            $nombreFinal = uniqid() . "_" . $nombreOriginal;
                            $rutaFinal = $uploadDir . $nombreFinal;

                            if (move_uploaded_file($archivos['tmp_name'][$i], $rutaFinal)) {
                                // Guardar archivo en la base de datos
                                $sqlArchivo = "INSERT INTO referencias_archivos (Referencia_id, Nombre, Ruta) VALUES (?, ?, ?)";
                                $stmtArchivo = $con->prepare($sqlArchivo);
                                $stmtArchivo->execute([$referencia_id, $nombreOriginal, $rutaFinal]);
                            }
                        }
                    }


                    $con->commit(); // Confirma la transacción
                    echo json_encode([
                        'success' => true,
                        'numero' => $numero,
                        'mensaje' => 'Referencia guardada correctamente.'
                    ]);

                }
            } else {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'Error al preparar la consulta.'
                ]);

            }
        } catch (PDOException $e) {
            $con->rollBack(); // Revertir cambios si algo falla

            echo "Código de error: " . $e->getCode() . "<br>";
            echo "Mensaje: " . $e->getMessage();
        }
    }
}
?>