<?php
include('../conexion.php');

if (isset($_POST['id'])) {
    // Funciones auxiliares
    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }

    function parseFecha($fecha) {
        return !empty($fecha) ? date("Y-m-d H:i:s", strtotime($fecha)) : null;
    }

    function parseHora($hora) {
        return !empty($hora) ? date("H:i:s", strtotime($hora)) : null;
    }

    // Datos recibidos
    $id = $_POST['id']; // ID de la referencia a actualizar

    $mercancia = $_POST['mercancia'] ?? null;
    $marcas = $_POST['marcas'] ?? null;
    $pedimento = $_POST['pedimento'] ?? null;
    $clave_ped = $_POST['clave_pedimento'] ?? null;
    $peso = isset($_POST['peso']) && $_POST['peso'] !== '' ? floatval($_POST['peso']) : null;
    $cantidad = isset($_POST['cantidad']) && $_POST['cantidad'] !== '' ? intval($_POST['cantidad']) : null;
    $bultos = isset($_POST['bultos']) && $_POST['bultos'] !== '' ? intval($_POST['bultos']) : null;
    $contenedor = $_POST['contenedor'] ?? null;
    $consolidadora = isset($_POST['consolidadora']) && $_POST['consolidadora'] !== '' ? intval($_POST['consolidadora']) : null;
    $resultado_mod = $_POST['resultado_mod'] ?? null;
    $recinto = isset($_POST['recinto']) && $_POST['recinto'] !== '' ? intval($_POST['recinto']) : null;
    $naviera = isset($_POST['naviera_id']) && $_POST['naviera_id'] !== '' ? intval($_POST['naviera_id']) : null;
    $cierre_doc = parseFecha($_POST['cierre_doc'] ?? null);
    $fecha_pago = parseFecha($_POST['fecha_pago'] ?? null);
    $buque = isset($_POST['buque_id']) && $_POST['buque_id'] !== '' ? intval($_POST['buque_id']) : null;
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

    $fecha_modificacion = obtenerFechaHoraActual();
    $usuario_modificacion = 1;
    try {
        $con->beginTransaction();

        $sql = "
            UPDATE referencias SET
                Mercancia = ?, Marcas = ?, Pedimentos = ?, ClavePedimento = ?, PesoBruto = ?, Cantidad = ?, Bultos = ?,
                Contenedor = ?, ConsolidadoraId = ?, ResultadoModulacion = ?, RecintoId = ?, NavieraId = ?, CierreDocumentos = ?,
                FechaPago = ?, BuqueId = ?, Booking = ?, CierreDespacho = ?, HoraDespacho = ?, Viaje = ?, SuReferencia = ?,
                CierreDocumentado = ?, LlegadaEstimada = ?, PuertoDescarga = ?, PuertoDestino = ?, Comentarios = ?, 
                FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
            WHERE Id = ?
        ";

        $params = [
            $mercancia, $marcas, $pedimento, $clave_ped, $peso, $cantidad, $bultos,
            $contenedor, $consolidadora, $resultado_mod, $recinto, $naviera, $cierre_doc,
            $fecha_pago, $buque, $booking, $cierre_desp, $hora_desp, $viaje, $su_referencia,
            $fecha_doc, $fecha_eta, $puerto_dec, $puerto_dest, $comentarios,
            $fecha_modificacion, $usuario_modificacion, $id
        ];

        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Subida de archivos
        $uploadDir = '../../../docs/';
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

        $con->commit();
        echo "Referencia guardada correctamente.";
    } catch (PDOException $e) {
        $con->rollBack();
        echo "Error al actualizar la referencia: " . $e->getMessage();
    }
} else {
    echo "Error: ID de la referencia no proporcionado.";
}
