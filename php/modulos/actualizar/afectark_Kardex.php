<?php
session_start();
include('../conexion.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    header('Content-Type: application/json');

    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit;
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
        exit;
    }

    $id = $_POST['id'];
    $referenciaId = $id;

    //---------------------------------------------OBTENER LA REFERENCIA-------------------------------------------------------------------
    $sql_referencia = "SELECT * FROM conta_referencias WHERE Id = :id";
    $stmt = $con->prepare($sql_referencia);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $referencia = $stmt->fetch(PDO::FETCH_ASSOC);



    //----------------------------------------------------BLOQUE PARTIDAS----------------------------------------------------------------------------------------
    $stmtPartidas = $con->prepare("SELECT * FROM conta_partidaspolizas WHERE ReferenciaId = :id");
    $stmtPartidas->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtPartidas->execute();
    $partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partidas)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron partidas']);
        exit;
    }

    //Se obtienen las cuentas 123 y 114
    $stmtPartidas = $con->prepare("
        SELECT 
            c.Id AS CuentaId,
            c.Numero,
            c.Nombre,
            p.Cargo,
            p.Abono,
            p.Observaciones
        FROM conta_partidaspolizas p
        INNER JOIN cuentas c ON p.SubcuentaId = c.Id
        WHERE p.ReferenciaId = :id
        AND c.CuentaPadreId IN (5, 14)
    ");
    $stmtPartidas->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtPartidas->execute();
    $resultados1 = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

    // Formar el arreglo asociativo
    $subcuentas1 = [];
    foreach ($resultados1 as $fila1) {
        $subcuentas1[] = [
            $fila1['CuentaId'],
            $fila1['Numero'],
            $fila1['Nombre'],
            $fila1['Cargo'],
            $fila1['Abono'],
            $fila1['Observaciones']
        ];
    }
    //Se suman todos los abonos
    $subtotal = 0;
    foreach ($subcuentas1 as $subcuenta) {
        $subtotal += floatval($subcuenta[4]); // Índice 3 = Abono
    }

    //Se obtienen las cuentas 214
    $stmtPartidas = $con->prepare("
            SELECT 
                c.Id AS CuentaId,
                c.Numero,
                c.Nombre,
                p.Cargo,
                p.Abono,
                p.Observaciones
            FROM conta_partidaspolizas p
            INNER JOIN cuentas c ON p.SubcuentaId = c.Id
            WHERE p.ReferenciaId = :id
            AND c.CuentaPadreId = 19
        ");
    $stmtPartidas->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtPartidas->execute();
    $resultados2 = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

    // Formar el arreglo asociativo
    $subcuentas2 = [];
    foreach ($resultados2 as $fila2) {
        $subcuentas2[] = [
            $fila2['CuentaId'],
            $fila2['Numero'],
            $fila2['Nombre'],
            $fila2['Cargo'],
            $fila2['Abono'],
            $fila2['Observaciones']
        ];
    }
    //Se suman todos los cargos
    $totalAnticipos = 0;
    foreach ($subcuentas2 as $subcuenta) {
        $totalAnticipos += floatval($subcuenta[3]); // Índice 3 = Cargo
    }
    $saldo = $subtotal - $totalAnticipos; //Gastos 123 y 114, Anticipos 214

    //---------------------------------------------OBTENER EL ÚLTIMO NumPoliza  
    $prefijo = 'D';
    $sql_ultimo = "SELECT Numero FROM conta_polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
    $stmt_ultimo = $con->prepare($sql_ultimo);
    $stmt_ultimo->execute([$prefijo]);

    $ultimo_numero = 0;
    if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
        $ultimo_numero = (int) substr($fila['Numero'], 1);
    }

    if ($referencia) {
        $empresa = 2;
        $activo = 1;
        $importe = $subtotal;
        $fechaActual = date('Y-m-d H:i:s');
        $usuarioAlta = $_SESSION['usuario_id'];

        $nuevo_numero = $ultimo_numero + 1;
        $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

        //---------------------------------------------SE CREA LA POLIZA
        $sql_poliza = "INSERT INTO conta_polizas
        (EmpresaId, Numero, Importe, Fecha, FechaAlta, UsuarioAlta, Activo)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_guardar = $con->prepare($sql_poliza);
        $stmt_guardar->execute([
            $empresa,
            $numero_poliza,
            $importe,
            $fechaActual,
            $fechaActual,
            $usuarioAlta,
            $activo
        ]);

        $polizaId = $con->lastInsertId();

        // ---------------------------------------------------------------OBTENER EL ÚLTIMO NumCg---------------------------------------------------------------------
        $referenciaId = $id;
        $numero = $referencia['Numero'];
        $logistico = $referencia['ClienteLogisticoId'];
        $exportador = $referencia['ClienteExportadorId'];
        $barco = $referencia['BuqueId'];
        $booking = $referencia['Booking'];
        $suReferencia = $referencia['SuReferencia'];
        $aduanaId = $referencia['AduanaId'];
        $status = 1;

        $sql_ultimo_cg = "SELECT NumCg FROM conta_cuentas_kardex WHERE NumCg LIKE 'CG-%' ORDER BY CAST(SUBSTRING(NumCg, 4) AS UNSIGNED) DESC LIMIT 1";
        $stmt_ultimo_cg = $con->prepare($sql_ultimo_cg);
        $stmt_ultimo_cg->execute();
        $ultimo_numcg = $stmt_ultimo_cg->fetchColumn();

        if ($ultimo_numcg) {
            $numero_actual = (int) substr($ultimo_numcg, 3);
            $numero_siguiente = $numero_actual + 1;
        } else {
            $numero_siguiente = 1;
        }

        $numero = 'CG-' . str_pad($numero_siguiente, 6, '0', STR_PAD_LEFT);

        //---------------------------------------------------------------INSERTAR EN CUENTAS KARDEX--------------------------------------------------------------------
        $sql_guardar = "INSERT INTO conta_cuentas_kardex     
        (NumCg, Referencia, Logistico, Exportador, Barco, Booking, SuReferencia, Importe, Anticipos, Saldo, Fecha, Poliza_id, NumPoliza, Status, Created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_guardar = $con->prepare($sql_guardar);
        $stmt_guardar->execute([
            $numero,
            $referenciaId,
            $logistico,
            $exportador,
            $barco,
            $booking,
            $suReferencia,
            $subtotal,
            $totalAnticipos,
            $saldo,
            $fechaActual,
            $polizaId,
            $numero_poliza,
            $status,
            $usuarioAlta
        ]);

        //----------------------------------------------------INSERCIÓN DE PARTIDAS----------------------------------------------------------------------------------------

        $sql_partida = "INSERT INTO conta_partidaspolizas     
        (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, EnKardex)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_partida = $con->prepare($sql_partida);

        // Insertar partidas de subcuentas1
        foreach ($subcuentas1 as $fila1) {
            $subcuentaId = $fila1[0];
            $nuevoCargo = $fila1[4];       // Abono → Cargo
            $nuevoAbono = $fila1[3];       // Cargo → Abono
            $observaciones = $fila1[5];
            $activo = 1;
            $enKardex = 1;

            $stmt_partida->execute([
                $polizaId,
                $subcuentaId,
                $referenciaId,
                $nuevoCargo,
                $nuevoAbono,
                $observaciones,
                $activo,
                $enKardex
            ]);
        }

        // Insertar partidas de subcuentas2
        foreach ($subcuentas2 as $fila2) {
            $subcuentaId = $fila2[0];
            $nuevoCargo = $fila2[4];      // Abono → Cargo
            $nuevoAbono = $fila2[3];      // Cargo → Abono
            $observaciones = $fila2[5];
            $activo = 1;
            $enKardex = 1;

            $stmt_partida->execute([
                $polizaId,
                $subcuentaId,
                $referenciaId,
                $nuevoCargo,
                $nuevoAbono,
                $observaciones,
                $activo,
                $enKardex
            ]);
        }

        //----------------------------------------------------PARTIDA DEL CLIENTE--------------------------------------------------------------------------------
        $sql_kardex = "SELECT NumCg, Saldo FROM conta_cuentas_kardex WHERE Referencia = :id";
        $stmt = $con->prepare($sql_kardex);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $kardex_info = $stmt->fetch(PDO::FETCH_ASSOC);

        $saldo = $kardex_info['Saldo'];

        // Insertar partida del Cliente
        switch ((int) $aduanaId) {
            case 74:
                $subcuentaCliente = 48;
                break;
            case 25:
                $subcuentaCliente = 54;
                break;
            case 119:
                $subcuentaCliente = 55;
                break;
            case 126:
                $subcuentaCliente = 56;
                break;
            case 81:
                $subcuentaCliente = 57;
                break;
            case 91:
                $subcuentaCliente = 58;
                break;
            default:
                $subcuentaCliente = 0;
                break;
        }

        $cargoKardex = 0;
        $abonoKardex = 0;

        if ($saldo < 0) {
            $abonoKardex = abs($saldo);
        } else {
            $cargoKardex = $saldo;
        }

        if ($saldo != 0) {
            // Insertar partida Cliente
            $sql_cuentaCliente = "INSERT INTO conta_partidaspolizas     
                (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Activo, EnKardex)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_cuentaCli = $con->prepare($sql_cuentaCliente);
            if (
                !$stmt_cuentaCli->execute([
                    $polizaId,
                    $subcuentaCliente,
                    $referenciaId,
                    $cargoKardex,
                    $abonoKardex,
                    1,
                    1
                ])
            ) {
                $errorInfo = $stmt_cuentaCli->errorInfo();
                throw new Exception("Error insertando partida cliente: " . implode(" | ", $errorInfo));
            }
        }


        //----------------------------------------------------BLOQUE FINAL----------------------------------------------------------------------------------------
        //SE ACTUALIZA LA REFERENCIA
        $sql_statusRef = "UPDATE conta_referencias SET Status = 3, FechaKardex = NOW() WHERE Id = :referenciaId";
        $stmt = $con->prepare($sql_statusRef);
        $stmt->execute([
            ':referenciaId' => $referenciaId
        ]);


        echo json_encode([
            'success' => true,
            'data' => $resultados
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la referencia']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>