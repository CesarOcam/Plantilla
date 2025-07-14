<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('../conexion.php');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}
header('Content-Type: application/json');

if (isset($_POST['aduana'], $_POST['exportador'], $_POST['logistico'])) {

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

    function parseInt($valor)
    {
        return (is_numeric($valor)) ? intval($valor) : null;
    }

    $aduana = trim($_POST['aduana']) ?: null;
    $exportador = trim($_POST['exportador']) ?: null;
    $logistico = trim($_POST['logistico']) ?: null;
    $mercancia = $_POST['mercancia'] ?? null;
    $marcas = $_POST['marcas'] ?? null;
    $pedimento = $_POST['pedimento'] ?? null;
    $clave_ped = $_POST['clave'] ?? null;
    $peso = $_POST['peso'] ?? 0.0;
    $peso = is_numeric($peso) ? (float) $peso : 0.0;
    $bultos = $_POST['bultos'] ?? null;
    $contenedor = $_POST['contenedor'] ?? null;
    $consolidadora = parseInt($_POST['consolidadora'] ?? null);
    $resultado_mod = $_POST['resultado_mod'] ?? null;
    $recinto = $_POST['recinto'] ?? null;
    $naviera = $_POST['naviera'] ?? null;
    $cierre_doc = parseFecha($_POST['cierre_doc'] ?? null);
    $fecha_pago = parseFecha($_POST['fecha_pago'] ?? null);
    $buque = $_POST['buque'] ?? null;
    $booking = $_POST['booking'] ?? null;
    $cierre_desp = parseFecha($_POST['cierre_desp'] ?? null);
    $hora_desp = parseHora($_POST['hora_desp'] ?? null);
    $viaje = $_POST['viaje'] ?? null;
    $su_referencia = $_POST['su_referencia'] ?? null;
    $fecha_doc = parseFecha($_POST['fecha_doc'] ?? null);
    $fecha_eta = parseFecha($_POST['fecha_eta'] ?? null);
    $puerto_dec = $_POST['puerto_desc'] ?? null;
    $puerto_dest = $_POST['puerto_dest'] ?? null;
    $comentarios = $_POST['comentarios'] ?? null;

    $fecha_alta = obtenerFechaHoraActual();
    $usuarioAlta = $_SESSION['usuario_id'];

    $sqlAduana = "SELECT nombre_corto_aduana FROM 2201aduanas WHERE id2201aduanas = ?";
    $stmtAduana = $con->prepare($sqlAduana);
    $stmtAduana->execute([$aduana]);
    $nombreCorto = $stmtAduana->fetchColumn();

    if (!$nombreCorto) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'No se encontró nombre corto para la aduana seleccionada.'
        ]);
        exit;
    }

    // Convertimos a mayúsculas y limpiamos espacios
    $nombreCortoLimpio = strtoupper(trim($nombreCorto));

    // EXCEPCIÓN personalizada para AIFA
    if ($nombreCortoLimpio === 'AIFA') {
        $letra = 'F'; // Fuerza prefijo 'F' para AIFA
    } else {
        $letra = substr($nombreCortoLimpio, 0, 1);
    }

    $anioDigito = date('Y') % 10;
    $prefijo = $letra . $anioDigito;
    $activo = 1;

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

    $sql = "INSERT INTO referencias (
        AduanaId, Numero, ClienteExportadorId, ClienteLogisticoId, Mercancia, Marcas,
        Pedimentos, ClavePedimento, PesoBruto, Bultos,
        Contenedor, ConsolidadoraId, ResultadoModulacion, RecintoId,
        NavieraId, CierreDocumentos, FechaPago, BuqueId, Booking, CierreDespacho,
        HoraDespacho, Viaje, SuReferencia, CierreDocumentado, LlegadaEstimada,
        PuertoDescarga, PuertoDestino, Comentarios, FechaAlta, Status, UsuarioAlta
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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

    if (count($params) !== substr_count($sql, '?')) {
        echo json_encode(['success' => false, 'mensaje' => 'Error: número de parámetros incorrecto.']);
        exit;
    }

    try {
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = implode(',', $v); // O convierte a string según convenga
            }
        }

        $con->beginTransaction();

        $stmt = $con->prepare($sql);
        if ($stmt->execute($params)) {
            $referencia_id = $con->lastInsertId();

            // Asegurar que recibimos arrays o arrays vacíos
            $contenedores = isset($_POST['contenedor']) ? (array) $_POST['contenedor'] : [];
            $tipos = isset($_POST['tipo']) ? (array) $_POST['tipo'] : [];
            $sellos = isset($_POST['sello']) ? (array) $_POST['sello'] : [];

            $sqlContenedor = "INSERT INTO contenedores (referencia_id, codigo, tipo, sello, status) VALUES (?, ?, ?, ?, 1)";
            $stmtContenedor = $con->prepare($sqlContenedor);

            $total = max(count($contenedores), count($tipos), count($sellos));

            for ($i = 0; $i < $total; $i++) {
                // Obtener valores o null
                $cont = $contenedores[$i] ?? null;
                $tipo = $tipos[$i] ?? null;
                $sello = $sellos[$i] ?? null;

                // Si el valor es array, convertir a string separada por comas o saltar
                if (is_array($cont)) {
                    $cont = implode(',', $cont);
                }
                if (is_array($tipo)) {
                    $tipo = implode(',', $tipo);
                }
                if (is_array($sello)) {
                    $sello = implode(',', $sello);
                }

                // Solo insertar si contenedor no está vacío
                if (!empty($cont)) {
                    $stmtContenedor->execute([$referencia_id, $cont, $tipo, $sello]);
                }
            }



            $uploadBaseDir = '../../../docs/';
            $uploadDir = $uploadBaseDir . $referencia_id . '/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $archivos = $_FILES['documentos'] ?? null;
            $total = (is_array($archivos) && isset($archivos['name']) && is_array($archivos['name']))
                ? count($archivos['name']) : 0;

            for ($i = 0; $i < $total; $i++) {
                if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                    $nombreOriginal = basename($archivos['name'][$i]);
                    $nombreFinal = uniqid() . "_" . $nombreOriginal;
                    $rutaFinal = $uploadDir . $nombreFinal;

                    if (move_uploaded_file($archivos['tmp_name'][$i], $rutaFinal)) {
                        $sqlArchivo = "INSERT INTO referencias_archivos (Referencia_id, Nombre, Ruta) VALUES (?, ?, ?)";
                        $stmtArchivo = $con->prepare($sqlArchivo);
                        $stmtArchivo->execute([$referencia_id, $nombreOriginal, $rutaFinal]);
                    }
                }
            }

            $con->commit();
            echo json_encode([
                'success' => true,
                'numero' => $numero,
                'mensaje' => 'Referencia guardada correctamente.'
            ]);
        } else {
            throw new PDOException("Error al ejecutar la inserción.");
        }
    } catch (PDOException $e) {
        $con->rollBack();
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error en la base de datos',
            'codigo' => $e->getCode(),
            'error' => $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan campos obligatorios.'
    ]);
}
?>