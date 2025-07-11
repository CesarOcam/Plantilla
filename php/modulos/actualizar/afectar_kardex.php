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

    $stmtPartidas = $con->prepare("SELECT * FROM partidaspolizas WHERE ReferenciaId = :id");
    $stmtPartidas->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtPartidas->execute();
    $partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partidas)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron partidas']);
        exit;
    }

    $polizaId = $partidas[0]['PolizaId'];
    $resultados = [];
    $subtotal = 0;
    $totalAnticipos = 0;
    $partidas_a_insertar = [];

    //-------------------------------------------------------------------------------------------------------------------------------
    // --- Cuentas 123 y 114 ---
    foreach ($partidas as $partida) {
        $subcuentaId = $partida['SubcuentaId'];
        $cargo = $partida['Cargo'];

        $stmtCuenta = $con->prepare("
        SELECT * 
        FROM cuentas 
        WHERE Numero IN (123, 114) AND Id = :id
        ");
        $stmtCuenta->bindParam(':id', $subcuentaId, PDO::PARAM_INT);
        $stmtCuenta->execute();
        $cuenta = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

        if ($cuenta) {
            $subtotal += $cargo;
            $resultados['detalles'][] = [
                'cuenta' => $cuenta['Nombre'],
                'cargo' => $cargo
            ];

            $partidas_a_insertar[] = [
                'PolizaId' => $polizaId,
                'SubcuentaId' => $subcuentaId,
                'ReferenciaId' => $id,
                'Cargo' => $cargo,
                'Abono' => 0,
                'Observaciones' => 'Partida de 123/114',
                'Activo' => 1,
                'Importe' => $cargo
            ];
        }
    }

    $resultados['subtotal'] = $subtotal;

    //-------------------------------------------------------------------------------------------------------------------------------
    // --- Cuentas 214 ---
    foreach ($partidas as $partida) {
        $subcuentaId = $partida['SubcuentaId'];
        $cargo = $partida['Cargo'];

        $stmtCuenta = $con->prepare("
        SELECT * 
        FROM cuentas 
        WHERE Id = :id AND Numero = 214
        ");
        $stmtCuenta->bindParam(':id', $subcuentaId, PDO::PARAM_INT);
        $stmtCuenta->execute();
        $cuenta = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

        if ($cuenta) {
            $totalAnticipos += $cargo;

            $stmtPoliza = $con->prepare("SELECT * FROM polizas WHERE Id = :id");
            $stmtPoliza->bindParam(':id', $polizaId, PDO::PARAM_INT);
            $stmtPoliza->execute();
            $poliza = $stmtPoliza->fetch(PDO::FETCH_ASSOC);

            $fechaFormateada = null;
            if (!empty($poliza['Fecha'])) {
                $fecha = new DateTime($poliza['Fecha']);
                $fechaFormateada = $fecha->format('(d-m-y)');
            }

            $resultados['anticipos'][] = [
                'cuenta' => $cuenta['Nombre'],
                'cargo' => $cargo,
                'fechaAnticipo' => $fechaFormateada
            ];

            $partidas_a_insertar[] = [
                'PolizaId' => $polizaId,
                'SubcuentaId' => $subcuentaId,
                'ReferenciaId' => $id,
                'Cargo' => 0,
                'Abono' => $cargo,
                'Observaciones' => 'Anticipo 214',
                'Activo' => 1,
                'Importe' => $cargo
            ];
        }
    }

    $resultados['totalAnticipos'] = $totalAnticipos;
    $saldo = $subtotal - $totalAnticipos;

    //-------------------------------------------------------------------------------------------------------------------------------
    // --- POLIZA y KARDEX ---
    $sql_referencia = "SELECT * FROM referencias WHERE Id = :id";
    $stmt = $con->prepare($sql_referencia);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $referencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $prefijo = 'D';
    $sql_ultimo = "SELECT Numero FROM polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
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

        $sql_poliza = "INSERT INTO polizas
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
        $numero = $referencia['Numero'];
        $logistico = $referencia['ClienteLogisticoId'];
        $exportador = $referencia['ClienteExportadorId'];
        $barco = $referencia['BuqueId'];
        $booking = $referencia['Booking'];
        $suReferencia = $referencia['SuReferencia'];
        $aduanaId = $referencia['AduanaId'];
        $status = 1;

        // OBTENER EL ÚLTIMO NumCg
        $sql_ultimo_cg = "SELECT NumCg FROM cuentas_kardex WHERE NumCg LIKE 'CG-%' ORDER BY CAST(SUBSTRING(NumCg, 4) AS UNSIGNED) DESC LIMIT 1";
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


        $sql_guardar = "INSERT INTO cuentas_kardex     
        (NumCg, Referencia, Logistico, Exportador, Barco, Booking, SuReferencia, Importe, Anticipos, Saldo, Fecha, Poliza_id, NumPoliza, Status, Created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_guardar = $con->prepare($sql_guardar);
        $stmt_guardar->execute([
            $numero,
            $id,
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

        // Insertar partidas
        $sql_partida = "INSERT INTO partidaspolizas     
        (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, EnKardex)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_partida = $con->prepare($sql_partida);

        foreach ($partidas_a_insertar as $p) {
            $stmt_partida->execute([
                $polizaId,
                $p['SubcuentaId'],
                $p['ReferenciaId'],
                $p['Abono'],
                $p['Cargo'],
                $p['Observaciones'],
                $p['Activo'],
                1
            ]);
        }

        $sql_kardex = "SELECT Importe, Anticipos FROM cuentas_kardex WHERE Referencia = :id";
        $stmt = $con->prepare($sql_kardex);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $kardex_info = $stmt->fetch(PDO::FETCH_ASSOC);

        $importe = $kardex_info['Importe'];
        $anticipos = $kardex_info['Anticipos'];

        $saldo = $importe - $anticipos;

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

        $cargo = 0;
        $abono = 0;

        if ($saldo < 0) {
            $abono = abs($saldo);
        } else {
            $cargo = $saldo;
        }

        if ($saldo != 0) {
            // Insertar partida Cliente
            $sql_cuentaCliente = "INSERT INTO partidaspolizas     
                (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Activo, EnKardex)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_cuentaCli = $con->prepare($sql_cuentaCliente);
            if (
                !$stmt_cuentaCli->execute([
                    $polizaId,
                    $subcuentaCliente,
                    $referenciaId,
                    $cargo,
                    $abono,
                    1,
                    1
                ])
            ) {
                $errorInfo = $stmt_cuentaCli->errorInfo();
                throw new Exception("Error insertando partida cliente: " . implode(" | ", $errorInfo));
            }

        }

        //actualizar referencia
        $sql_statusRef = "UPDATE referencias SET Status = 3 WHERE Id = :referenciaId";
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