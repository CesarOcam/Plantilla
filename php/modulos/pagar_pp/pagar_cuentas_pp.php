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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['ids'] ?? '';
    $total = $_POST['total'] ?? 0;
    $fecha = $_POST['fecha'] ?? '';
    $beneficiario = $_POST['beneficiario'] ?? '';
    $subcuenta = $_POST['subcuenta'] ?? '';

    $empresa = 2;
    $usuarioAlta = $_SESSION['usuario_id'];
    $numero_poliza = '';
    $prefijo = 'D'; // Siempre prefijo D
    $concepto = 'PAGO DE CUENTA';
    $activo = 1;

    $sql_ultimo = "SELECT Numero FROM polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
    $stmt_ultimo = $con->prepare($sql_ultimo);
    $stmt_ultimo->execute([$prefijo]);

    $ultimo_numero = 0;
    if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
        $ultimo_numero = (int) substr($fila['Numero'], 1); // Extraer número sin prefijo
    }

    $nuevo_numero = $ultimo_numero + 1;
    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

    //---------------------------------------GENERAR POLIZA--------------------------------------------------------
    $sql_insert_poliza = "INSERT INTO polizas 
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

    $poliza_id = $con->lastInsertId();
    //-------------------------------------GENERAR PARTIDAS----------------------------------------------------------

    try {
        $con->beginTransaction();
        $id_array = array_filter(explode(',', $ids)); // Quitamos vacíos
        $cargo = 0;
        $observacion = 'Pago de cuenta';
        $enKardex = 0;
        $Pagada = 1;

        $sql_datos_partida = "SELECT PolizaId, SubcuentaId, ReferenciaId, Cargo FROM partidaspolizas WHERE Partida = ?";
        $stmt_datos = $con->prepare($sql_datos_partida);

        $sql_insert_partida = "INSERT INTO partidaspolizas 
            (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Pagada, Observaciones, Activo, EnKardex, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $con->prepare($sql_insert_partida);

        $sql_update_pagada_original = "UPDATE partidaspolizas SET Pagada = 1 WHERE Partida = ?";
        $stmt_update_original = $con->prepare($sql_update_pagada_original);

        foreach ($id_array as $id_partida) {
            $stmt_datos->execute([$id_partida]);
            $datos = $stmt_datos->fetch(PDO::FETCH_ASSOC);

            if ($datos) {
                $subcuentaId = $datos['SubcuentaId'];
                $referenciaId = $datos['ReferenciaId'];
                $cargoAbonado = $datos['Cargo'];
                $polizaOriginalId = $datos['PolizaId'];

                // Insertamos la nueva partida relacionada a la nueva póliza
                $ok = $stmt_insert->execute([
                    $poliza_id,
                    $subcuentaId,
                    $referenciaId,
                    $cargo,
                    $cargoAbonado,
                    $Pagada,
                    $observacion,
                    $activo,
                    $enKardex,
                    $usuarioAlta
                ]);

                if (!$ok) {
                    $error = $stmt_insert->errorInfo();
                    throw new Exception("Error al insertar partida: " . $error[2]);
                }

                // Actualizamos la póliza original a pagada
                $okOriginal = $stmt_update_original->execute([$id_partida]);

                if (!$okOriginal) {
                    $error = $stmt_update_original->errorInfo();
                    throw new Exception("Error al actualizar póliza original (ID: $polizaOriginalId): " . $error[2]);
                }

            } else {
                throw new Exception("No se encontró partida con ID: $id_partida");
            }
        }

        // === Insertar la partida de salida bancaria ===
        $sql_insert_banco = "INSERT INTO partidaspolizas 
            (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Pagada, Observaciones, Activo, EnKardex, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_banco = $con->prepare($sql_insert_banco);

        $referenciaBanco = null;
        $cargoBanco = $total;
        $abonoBanco = 0;
        $observacionBanco = 'Salida de banco por pago de cuentas';

        $okBanco = $stmt_banco->execute([
            $poliza_id,
            $subcuenta,
            $referenciaBanco,
            $cargoBanco,
            $abonoBanco,
            $Pagada,
            $observacionBanco,
            $activo,
            $enKardex,
            $usuarioAlta
        ]);

        if (!$okBanco) {
            $error = $stmt_banco->errorInfo();
            throw new Exception("Error al insertar partida bancaria: " . $error[2]);
        }

        // === Marcar la nueva póliza como pagada ===
        $sql_update_poliza = "UPDATE polizas SET Pagada = 1 WHERE Id = ?";
        $stmt_update = $con->prepare($sql_update_poliza);
        $okUpdate = $stmt_update->execute([$poliza_id]);

        if (!$okUpdate) {
            $error = $stmt_update->errorInfo();
            throw new Exception("Error al actualizar estado de póliza nueva: " . $error[2]);
        }

        $con->commit();


        echo json_encode([
            'success' => true,
            'mensaje' => 'Pago registrado correctamente.',
            'datos' => [
                'ids' => $id_array,
                'total' => $total,
                'fecha' => $fecha,
                'beneficiario' => $beneficiario,
                'subcuenta' => $subcuenta,
                'poliza' => $numero_poliza
            ]
        ]);
        exit;
    } catch (Exception $e) {
        $con->rollBack();
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al guardar partidas: ' . $e->getMessage()
        ]);
        exit;
    }
}