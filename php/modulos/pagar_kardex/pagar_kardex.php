<?php
session_start();
header('Content-Type: application/json');

include('../conexion.php');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

// Verificar datos requeridos
if (
    isset($_POST['beneficiario'], $_POST['subcuenta'], $_POST['ids'], $_POST['total'])
) {
    $empresa = 2;
    $beneficiario = $_POST['beneficiario'];
    $subcuenta = $_POST['subcuenta'];
    $ids = $_POST['ids'];
    $total = $_POST['total'];
    $fecha = $_POST['fecha'];
    $usuarioAlta = $_SESSION['usuario_id'];
    $activo = 1;

    //---------------------------------------GENERAR POLIZA--------------------------------------------------------
    $numero_poliza = '';
    $prefijo = 'D'; // Siempre prefijo D
    $concepto = 'PAGO DE CUENTA';

    $sql_ultimo = "SELECT Numero FROM conta_polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
    $stmt_ultimo = $con->prepare($sql_ultimo);
    $stmt_ultimo->execute([$prefijo]);

    $ultimo_numero = 0;
    if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
        $ultimo_numero = (int) substr($fila['Numero'], 1); // Extraer número sin prefijo
    }

    $nuevo_numero = $ultimo_numero + 1;
    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);


    $sql_insert_poliza = "INSERT INTO conta_polizas 
    (
        BeneficiarioId, EmpresaId, Numero, Importe, Concepto, Fecha, Activo, FechaAlta, UsuarioAlta
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $beneficiario,
        $empresa,
        $numero_poliza,
        $total,
        $concepto,
        $fecha,
        $activo,
        $fecha,
        $usuarioAlta
    ];

    $stmt_poliza = $con->prepare($sql_insert_poliza);
    $resultado = $stmt_poliza->execute($params);

    if (!$resultado) {
        die("Error al guardar la póliza: " . implode(", ", $stmt_poliza->errorInfo()));
    }

    //-------------------------------------GENERAR PARTIDAS----------------------------------------------------------
    $poliza_id = $con->lastInsertId();
    $activo = 1;

    $ids = explode(',', $_POST['ids']);
    $referencias = [];

    $sql = "SELECT Referencia, Saldo FROM conta_cuentas_kardex WHERE Id = ?";
    $stmt = $con->prepare($sql);

    foreach ($ids as $id) {
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $referencias[] = [
                'id' => $id,
                'referencia' => $row['Referencia'],
                'saldo' => $row['Saldo']
            ];
        }
    }

    $referencias_con_aduana = [];

    $sqlRef = "SELECT AduanaId FROM conta_referencias WHERE Id = ?";
    $stmtRef = $con->prepare($sqlRef);

    foreach ($referencias as $ref) {
        $stmtRef->execute([$ref['referencia']]);
        $rowRef = $stmtRef->fetch(PDO::FETCH_ASSOC);

        if ($rowRef) {
            $aduana_id = $rowRef['AduanaId'];
            $cuenta = null;

            switch ($aduana_id) {
                case 25:
                    $cuenta = 54;
                    break;
                case 74:
                    $cuenta = 53;
                    break;
                case 81:
                    $cuenta = 57;
                    break;
                case 91:
                    $cuenta = 58;
                    break;
                case 119:
                    $cuenta = 55;
                    break;
                case 126:
                    $cuenta = 56;
                    break;
                default:
                    $cuenta = null;
            }

            $referencias_con_aduana[] = [
                'id' => $ref['id'],
                'referencia' => $ref['referencia'],
                'saldo' => $ref['saldo'],
                'aduana_id' => $aduana_id,
                'cuenta' => $cuenta
            ];
        }
    }

    // Ahora sí, calcular totales
    $total_cargos = 0;
    $total_abonos = 0;

    foreach ($referencias_con_aduana as $ref) {
        $importe = floatval($ref['saldo']);
        if ($importe > 0) {
            $total_cargos += $importe;
        } else {
            $total_abonos += abs($importe);
        }
    }

    // Insertar las partidas por cada referencia
    $sql_insert_partidas = "INSERT INTO conta_partidaspolizas 
    (Polizaid, Subcuentaid, ReferenciaId, Cargo, Abono, Activo, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_partidas = $con->prepare($sql_insert_partidas);

    foreach ($referencias_con_aduana as $ref) {
        $subcuenta_id = $ref['cuenta'];
        $importe = floatval($ref['saldo']);

        $cargo = $importe > 0 ? $importe : 0;
        $abono = $importe < 0 ? abs($importe) : 0;

        $stmt_partidas->execute([
            $poliza_id,
            $subcuenta_id,
            $ref['referencia'],
            $cargo,
            $abono,
            $activo,
            $usuarioAlta
        ]);
    }

    // Insertar partida de banco si es necesario
    $diferencia = $total_cargos - $total_abonos;

    if ($diferencia != 0) {
        $cargo = $diferencia < 0 ? abs($diferencia) : 0;
        $abono = $diferencia > 0 ? abs($diferencia) : 0;

        $sql_partida_banco = "INSERT INTO conta_partidaspolizas 
        (Polizaid, Subcuentaid, Cargo, Abono, Activo)
        VALUES (?, ?, ?, ?, ?)";
        $stmt_partida_banco = $con->prepare($sql_partida_banco);

        $stmt_partida_banco->execute([
            $poliza_id,
            $subcuenta, // este es el banco seleccionado por el usuario
            $cargo,
            $abono,
            $activo
        ]);
    }

    $sql_update = "UPDATE conta_cuentas_kardex SET Status = 2 WHERE Id = ?";
    $stmt_update = $con->prepare($sql_update);

    foreach ($ids as $id) {
        $stmt_update->execute([$id]);
    }


    echo json_encode([
        'success' => true,
        'mensaje' => 'Datos recibidos correctamente.',
        'beneficiario' => $beneficiario,
        'subcuenta' => $subcuenta,
        'total' => $total,
        'fecha' => $fecha,
    ]);
    exit;

} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Faltan datos en el formulario.'
    ]);
    exit;
}
?>