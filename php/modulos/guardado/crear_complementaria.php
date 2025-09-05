<?php
session_start();
header('Content-Type: application/json');
include('../conexion.php');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

if (isset($_POST['id'])) {

    $id = $_POST['id'];
    // Funciones auxiliares
    date_default_timezone_set("America/Mexico_City");
    $fecha = date("Y-m-d H:i:s");


    $sqlRef = "SELECT * FROM conta_referencias WHERE Id = ?";
    $stmRef = $con->prepare($sqlRef);
    $stmRef->execute([$id]);
    $referencia = $stmRef->fetch(PDO::FETCH_ASSOC);

    if (!$referencia) {
        echo json_encode(['success' => false, 'mensaje' => 'Referencia no encontrada']);
        exit;
    }

    $numero = $referencia['Numero'];
    // Si la referencia actual es complementaria, usamos la original
    $referenciaPadreId = $referencia['Id']; // por defecto, apuntamos a la referencia que estamos duplicando

    if (!empty($referencia['ReferenciaPadreId'])) {
        // Si ya tiene ReferenciaPadreId, es complementaria, entonces usamos la referencia original
        $referenciaPadreId = $referencia['ReferenciaPadreId'];
    }

    function incrementarNumero($numeroBase, $con)
    {
        // Extraer la base antes del primer guion
        $partes = explode('-', $numeroBase);
        $base = $partes[0];

        // Buscar todos los números que empiecen con la base
        $sql = "SELECT Numero FROM conta_referencias WHERE Numero LIKE ? ORDER BY Id DESC";
        $like = $base . '-%';
        $stmt = $con->prepare($sql);
        $stmt->execute([$like]);
        $numeros = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxSufijo = 0;

        foreach ($numeros as $num) {
            $subpartes = explode('-', $num);
            if (count($subpartes) === 2 && is_numeric($subpartes[1])) {
                $sufijo = intval($subpartes[1]);
                if ($sufijo > $maxSufijo) {
                    $maxSufijo = $sufijo;
                }
            }
        }

        $nuevoSufijo = $maxSufijo + 1;
        return $base . '-' . $nuevoSufijo;
    }
    $nuevoNumero = incrementarNumero($numero, $con);


    if ($referencia) {

        $sql = "INSERT INTO conta_referencias (
        ReferenciaPadreId,
        AduanaId, ClienteExportadorId, ClienteLogisticoId, Mercancia, FechaPago, Marcas,
        Pedimentos, ClavePedimento, PesoBruto, Cantidad,
        Contenedor, ConsolidadoraId, ResultadoModulacion, RecintoId, Numero,
        NavieraId, CierreDocumentos, BuqueId, Booking, CierreDespacho,
        HoraDespacho, Viaje, SuReferencia, CierreDocumentado, LlegadaEstimada,
        PuertoDescarga, PuertoDestino, Comentarios, FechaAlta, Status, UsuarioAlta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $referenciaPadreId,
            $referencia['AduanaId'],
            $referencia['ClienteExportadorId'],
            $referencia['ClienteLogisticoId'],
            $referencia['Mercancia'],
            $referencia['FechaPago'],
            $referencia['Marcas'],
            $referencia['Pedimentos'],
            $referencia['ClavePedimento'],
            $referencia['PesoBruto'],
            $referencia['Cantidad'],
            $referencia['Contenedor'],
            $referencia['ConsolidadoraId'],
            $referencia['ResultadoModulacion'],
            $referencia['RecintoId'],
            $nuevoNumero,
            $referencia['NavieraId'],
            $referencia['CierreDocumentos'],
            $referencia['BuqueId'],
            $referencia['Booking'],
            $referencia['CierreDespacho'],
            $referencia['HoraDespacho'],
            $referencia['Viaje'],
            $referencia['SuReferencia'],
            $referencia['CierreDocumentado'],
            $referencia['LlegadaEstimada'],
            $referencia['PuertoDescarga'],
            $referencia['PuertoDestino'],
            $referencia['Comentarios'],
            $fecha,
            2,
            $_SESSION['usuario_id']

        ];

        if (count($params) !== substr_count($sql, '?')) {
            echo json_encode(['success' => false, 'mensaje' => 'Error: número de parámetros incorrecto.']);
            exit;
        } else {
            $stmtInsert = $con->prepare($sql);
            $ejecutado = $stmtInsert->execute($params);

            if ($ejecutado) {
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Referencia Complementaria guardada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'Error al ejecutar el insert'
                ]);
            }
        }


    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error en la consulta'
        ]);
    }

} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Id de Referencia no proporcionado'
    ]);
}
