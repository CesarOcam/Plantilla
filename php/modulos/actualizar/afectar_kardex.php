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

    //Verificación de Pagos pendientes

    $stmVerificacion = $con->prepare("
    SELECT 
        ps.*,
        s.BeneficiarioId,
        s.Importe,
        s.Status
    FROM conta_partidassolicitudes ps
    JOIN conta_solicitudes s ON ps.SolicitudId = s.Id
    WHERE ps.ReferenciaId = :id
");
    $stmVerificacion->bindParam(':id', $id, PDO::PARAM_INT);
    $stmVerificacion->execute();
    $verificacion = $stmVerificacion->fetchAll(PDO::FETCH_ASSOC);

    $idsPendientes = [];

    foreach ($verificacion as $fila) {
        if ($fila['Status'] == 1) {
            $idsPendientes[] = $fila['SolicitudId']; // almacenar el ID de la solicitud pendiente
        }
    }

    if (!empty($idsPendientes)) {
        // Enviar error a AJAX con los IDs separados por comas
        echo json_encode([
            'success' => false,
            'message' => 'Existen solicitudes pendientes con No: ' . implode(', ', $idsPendientes)
        ]);
        exit;
    } else {
        // continuar con el proceso si no hay Status 1


        $stmtPartidas = $con->prepare("
        SELECT p.*
        FROM conta_partidaspolizas p
        JOIN cuentas c ON p.SubcuentaId = c.Id
        WHERE p.ReferenciaId = :id
        AND (
            c.Numero LIKE '123%' OR
            c.Numero LIKE '114%' OR
            c.Numero LIKE '214%'
        )
        AND p.Activo = 1
    ");

        $stmtPartidas->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtPartidas->execute();
        $partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

        if (empty($partidas)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron partidas']);
            exit;
        }

        $polizaId = $partidas[0]['PolizaId'];
        $resultados = [];
        $subcuentas_agregadas = [];
        $partidas_a_insertar = [];
        $subtotal = 0;
        $totalAnticipos = 0;
        $saldo = 0;
        $subcuentasValidas = [123, 114, 214];
        $UsuarioSolicitud = '';

        foreach ($partidas as $partida) {
            $subcuentaId = $partida['SubcuentaId'];

            // Verificar si la subcuenta pertenece a una cuenta válida (123, 114, 214)
            $stmtCuenta = $con->prepare("
        SELECT Numero, Nombre 
        FROM cuentas 
        WHERE Id = :id
    ");
            $stmtCuenta->bindParam(':id', $subcuentaId, PDO::PARAM_INT);
            $stmtCuenta->execute();
            $cuenta = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

            if (!$cuenta) {
                continue; // o maneja el error según el caso
            }

            $numeroCuenta = $cuenta['Numero'];
            $prefijoValido = false;

            foreach ($subcuentasValidas as $valida) {
                if (strpos($numeroCuenta, (string) $valida) === 0) {
                    $prefijoValido = true;
                    break;
                }
            }

            if (!$prefijoValido) {
                continue; // Si no empieza con 123, 114 o 214, saltar
            }
            // Obtener datos originales
            $cargo_original = $partida['Cargo'];
            $abono_original = $partida['Abono'];

            // Invertir cargo y abono (contrapartida)
            $cargo_nuevo = $abono_original;
            $abono_nuevo = $cargo_original;

            // Sumar a totales
            $subtotal += $cargo_nuevo;
            $totalAnticipos += $abono_nuevo;

            $observaciones = 'Contrapartida automática';
            $UsuarioSolicitud = $partida['UsuarioSolicitud'];

            $partidas_a_insertar[] = [
                'PolizaId' => $polizaId,
                'SubcuentaId' => $subcuentaId,
                'ReferenciaId' => $id,
                'Cargo' => $cargo_nuevo,
                'Abono' => $abono_nuevo,
                'Observaciones' => $observaciones,
                'Activo' => 1,
                'UsuarioSolicitud' => $UsuarioSolicitud,
            ];

            // Marcar esta subcuenta como ya insertada
            $subcuentas_agregadas[] = $subcuentaId;
        }

        //$saldo = $subtotal - $totalAnticipos;
        $saldo = $totalAnticipos - $subtotal;

        //-------------------------------------------------------------------------------------------------------------------------------
        // --- POLIZA y KARDEX ---
        $sql_referencia = "SELECT * FROM conta_referencias WHERE Id = :id";
        $stmt = $con->prepare($sql_referencia);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $referencia = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $numero = $referencia['Numero'];
            $logistico = $referencia['ClienteLogisticoId'];
            $exportador = $referencia['ClienteExportadorId'];
            $barco = $referencia['BuqueId'];
            $booking = $referencia['Booking'];
            $suReferencia = $referencia['SuReferencia'];
            $aduanaId = $referencia['AduanaId'];
            $status = 1;

            // OBTENER EL ÚLTIMO NumCg
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


            $sql_guardar = "INSERT INTO conta_cuentas_kardex
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
            $sql_partida = "INSERT INTO conta_partidaspolizas     
        (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Observaciones, Activo, EnKardex, UsuarioSolicitud, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_partida = $con->prepare($sql_partida);

            foreach ($partidas_a_insertar as $p) {
                $stmt_partida->execute([
                    $polizaId,
                    $p['SubcuentaId'],
                    $p['ReferenciaId'],
                    $p['Cargo'],
                    $p['Abono'],
                    $p['Observaciones'],
                    $p['Activo'],
                    1,
                    $p['UsuarioSolicitud'],
                    $usuarioAlta
                ]);
            }

            $sql_kardex = "SELECT Saldo FROM conta_cuentas_kardex WHERE Referencia = :id";
            $stmt = $con->prepare($sql_kardex);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $kardex_info = $stmt->fetch(PDO::FETCH_ASSOC);

            $saldoKardex = $kardex_info['Saldo'];

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

            $cargoF = 0;
            $abonoF = 0;

            if ($saldo > 0) {
                $cargoF = $saldo;  // si es 1486, se queda así
                $abonoF = 0;
            } else {
                $abonoF = -$saldo; // si es -1486, se vuelve 1486
                $cargoF = 0;
            }


            if ($saldo != 0) {
                // Insertar partida Cliente
                $sql_cuentaCliente = "INSERT INTO conta_partidaspolizas     
                (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Activo, EnKardex, UsuarioSolicitud, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_cuentaCli = $con->prepare($sql_cuentaCliente);
                if (
                    !$stmt_cuentaCli->execute([
                        $polizaId,
                        $subcuentaCliente,
                        $referenciaId,
                        $cargoF,
                        $abonoF,
                        1,
                        1,
                        $UsuarioSolicitud,
                        $usuarioAlta
                    ])
                ) {
                    $errorInfo = $stmt_cuentaCli->errorInfo();
                    throw new Exception("Error insertando partida cliente: " . implode(" | ", $errorInfo));
                }

            }

            //actualizar referencia
            $sql_statusRef = "UPDATE conta_referencias SET Status = 3, FechaKardex = NOW() WHERE Id = :referenciaId";
            $stmt = $con->prepare($sql_statusRef);
            $stmt->execute([
                ':referenciaId' => $referenciaId
            ]);


            echo json_encode([
                'success' => true,
                'data' => $resultados,
                'id' => $id,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró la referencia']);
        }
    }


} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>