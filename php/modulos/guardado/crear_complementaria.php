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

    $id = $_POST['id'];
    // Funciones auxiliares
    date_default_timezone_set("America/Mexico_City");
    $fecha = date("Y-m-d H:i:s");


    $sqlRef = "SELECT * FROM conta_referencias WHERE Id = ?";
    $stmRef = $con->prepare($sqlRef);
    $stmRef->execute([$id]);
    $referencia = $stmRef->fetch(PDO::FETCH_ASSOC);

    $numero = $referencia['Numero'];
    function incrementarNumero($numeroBase, $con)
    {
        $sql = "SELECT Numero FROM conta_referencias WHERE Numero LIKE ? ORDER BY Id DESC";
        $like = $numeroBase . '-%';
        $stmt = $con->prepare($sql);
        $stmt->execute([$like]);
        $numeros = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxSufijo = 0;

        foreach ($numeros as $num) {
            $partes = explode('-', $num);
            if (count($partes) === 2 && is_numeric($partes[1])) {
                $sufijo = intval($partes[1]);
                if ($sufijo > $maxSufijo) {
                    $maxSufijo = $sufijo;
                }
            }
        }

        $nuevoSufijo = $maxSufijo + 1;
        return $numeroBase . '-' . $nuevoSufijo;
    }
    
    $nuevoNumero = incrementarNumero($numero, $con);


    if ($referencia) {

        $sql = "INSERT INTO conta_referencias (
        AduanaId, ClienteExportadorId, ClienteLogisticoId, Mercancia, Marcas,
        Pedimentos, ClavePedimento, PesoBruto, Cantidad,
        Contenedor, ConsolidadoraId, ResultadoModulacion, RecintoId, Numero,
        NavieraId, CierreDocumentos, BuqueId, Booking, CierreDespacho,
        HoraDespacho, Viaje, SuReferencia, CierreDocumentado, LlegadaEstimada,
        PuertoDescarga, PuertoDestino, Comentarios, FechaAlta, Status, UsuarioAlta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $referencia['AduanaId'],
            $referencia['ClienteExportadorId'],
            $referencia['ClienteLogisticoId'],
            $referencia['Mercancia'],
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
            1,
            $referencia['UsuarioAlta']
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
