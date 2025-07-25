<?php
session_start();
include('../conexion.php');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}
include('../conexion.php');

if (isset($_POST['id'])) {
    // Funciones auxiliares
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

    // Datos recibidos
    $id = $_POST['id']; // ID de la referencia a actualizar

    $exportador = trim($_POST['exportador']) ?: null;
    $logistico = trim($_POST['logistico']) ?: null;
    $mercancia = $_POST['mercancia'] ?? null;
    $marcas = $_POST['marcas'] ?? null;
    $pedimento = $_POST['pedimento'] ?? null;
    $clave_ped = $_POST['clave'] ?? null;
    $peso = isset($_POST['peso']) && $_POST['peso'] !== '' ? floatval($_POST['peso']) : null;
    $bultos = isset($_POST['bultos']) && $_POST['bultos'] !== '' ? $_POST['bultos'] : null;
    $consolidadora = isset($_POST['consolidadora']) && $_POST['consolidadora'] !== '' ? intval($_POST['consolidadora']) : null;
    $resultado_mod = $_POST['modulacion'] ?? null;
    $resultado_mod = ($resultado_mod === '' ? null : (int) $resultado_mod);
    $recinto = isset($_POST['recinto']) && $_POST['recinto'] !== '' ? intval($_POST['recinto']) : null;
    $naviera_input = $_POST['naviera'] ?? '';
    $naviera = ($naviera_input === '' || $naviera_input == '0') ? null : intval($naviera_input);
    $cierre_doc = parseFecha($_POST['cierre_doc'] ?? null);
    $fecha_pago = parseFecha($_POST['fecha_pago'] ?? null);
    $buque = isset($_POST['buque']) && $_POST['buque'] !== '' ? intval($_POST['buque']) : null;
    $booking = $_POST['booking'] ?? null;
    $cierre_desp = parseFecha($_POST['cierre_desp'] ?? null);
    $hora_desp = parseHora($_POST['hora_desp'] ?? null);
    $viaje = $_POST['viaje'] ?? null;
    $su_referencia = $_POST['SuReferencia'] ?? null;
    $fecha_doc = parseFecha($_POST['fecha_doc'] ?? null);
    $fecha_eta = parseFecha($_POST['fecha_eta'] ?? null);
    $puerto_dec = $_POST['puerto_desc'] ?? null;
    $puerto_dest = $_POST['puerto_dest'] ?? null;
    $comentarios = $_POST['comentarios'] ?? null;

    $fecha_modificacion = obtenerFechaHoraActual();
    $usuario_modificacion = $_SESSION['usuario_id'];
    try {
        $con->beginTransaction();

        $sql = "
            UPDATE referencias SET
                ClienteExportadorId = ?, ClienteLogisticoId = ?, Mercancia = ?, Marcas = ?, Pedimentos = ?, ClavePedimento = ?, PesoBruto = ?, Cantidad = ?,
                ConsolidadoraId = ?, ResultadoModulacion = ?, RecintoId = ?, NavieraId = ?, CierreDocumentos = ?,
                FechaPago = ?, BuqueId = ?, Booking = ?, CierreDespacho = ?, HoraDespacho = ?, Viaje = ?, SuReferencia = ?,
                CierreDocumentado = ?, LlegadaEstimada = ?, PuertoDescarga = ?, PuertoDestino = ?, Comentarios = ?, 
                FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
            WHERE Id = ?
        ";

        $params = [
            $exportador,
            $logistico,
            $mercancia,
            $marcas,
            $pedimento,
            $clave_ped,
            $peso,
            $bultos,
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
            $fecha_modificacion,
            $usuario_modificacion,
            $id
        ];

        $stmt = $con->prepare($sql);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // esto puede ir antes

        if ($stmt->execute($params)) {
            $referencia_id = $id;

            $contenedores = isset($_POST['contenedor']) ? (array) $_POST['contenedor'] : [];
            $tipos = isset($_POST['tipo']) ? (array) $_POST['tipo'] : [];
            $sellos = isset($_POST['sello']) ? (array) $_POST['sello'] : [];
            $contenedor_ids = isset($_POST['contenedor_id']) ? (array) $_POST['contenedor_id'] : [];

            $sqlUpdateContenedor = "UPDATE contenedores SET codigo = ?, tipo = ?, sello = ? WHERE idcontenedor = ? AND referencia_id = ?";
            $stmtUpdateContenedor = $con->prepare($sqlUpdateContenedor);

            $sqlInsertContenedor = "INSERT INTO contenedores (referencia_id, codigo, tipo, sello, status) VALUES (?, ?, ?, ?, 1)";
            $stmtInsertContenedor = $con->prepare($sqlInsertContenedor);

            $total = max(count($contenedores), count($tipos), count($sellos), count($contenedor_ids));

            for ($i = 0; $i < $total; $i++) {
                $cont = $contenedores[$i] ?? null;
                $tipo = $tipos[$i] ?? null;
                $sello = $sellos[$i] ?? null;
                $idContenedor = $contenedor_ids[$i] ?? null;

                if (is_array($cont))
                    $cont = implode(',', $cont);
                if (is_array($tipo))
                    $tipo = implode(',', $tipo);
                if (is_array($sello))
                    $sello = implode(',', $sello);

                if (!empty($cont)) {
                    if (!empty($idContenedor)) {
                        // Actualizar si ya existe
                        $stmtUpdateContenedor->execute([$cont, $tipo, $sello, $idContenedor, $referencia_id]);
                    } else {
                        // Verificar si el contenedor ya existe para esta referencia para evitar duplicados
                        $sqlCheck = "SELECT COUNT(*) FROM contenedores WHERE referencia_id = ? AND codigo = ?";
                        $stmtCheck = $con->prepare($sqlCheck);
                        $stmtCheck->execute([$referencia_id, $cont]);
                        $existe = $stmtCheck->fetchColumn();

                        if (!$existe) {
                            // Insertar si es nuevo y no existe ya
                            $stmtInsertContenedor->execute([$referencia_id, $cont, $tipo, $sello]);
                        }
                    }
                }
            }
        }

        // Subida de archivos
        $uploadBaseDir = '../../../docs/';
        $uploadDir = $uploadBaseDir . $id . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

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
                    $sqlArchivo = "INSERT INTO referencias_archivos (Referencia_id, Nombre, Ruta) VALUES (?, ?, ?)";
                    $stmtArchivo = $con->prepare($sqlArchivo);
                    $stmtArchivo->execute([$id, $nombreOriginal, $rutaFinal]);
                }
            }
        }

        $contenedores_eliminados = isset($_POST['contenedores_eliminados']) ? (array) $_POST['contenedores_eliminados'] : [];

        if (!empty($contenedores_eliminados)) {
            $sqlDeleteContenedor = "DELETE FROM contenedores WHERE idcontenedor = ? AND referencia_id = ?";
            $stmtDeleteContenedor = $con->prepare($sqlDeleteContenedor);

            foreach ($contenedores_eliminados as $idContenedor) {
                $stmtDeleteContenedor->execute([$idContenedor, $referencia_id]);
            }
        }


        $con->commit();
        echo "Referencia guardada correctamente.";
    } catch (PDOException $e) {
        $con->rollBack();
        echo "Error al actualizar la referencia: " . $e->getMessage();
    }
} else {
    echo "Error: ID de la referencia no proporcionado.";
}
