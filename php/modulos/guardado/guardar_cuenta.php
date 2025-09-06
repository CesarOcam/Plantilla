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

if (isset($_POST['numero'], $_POST['nombre'], $_POST['tipo_saldo'])) {
    // Recoger todos los valores
    $numero = $_POST['numero'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo_saldo'];

    $subcuentas = $_POST['numero_subcuenta'] ?? [];
    $subcuenta_formateadas = array_map(function($sub) use ($numero) {
        return "$numero-$sub";
    }, $subcuentas);

    $nombre_subcuenta = $_POST['nombre_subcuenta'] ?? [];
    $saldo_subcuenta = $_POST['saldo_subcuenta'] ?? [];

    function obtenerFechaHoraActual()
    {
        
        return date("Y-m-d H:i:s");
    }

    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = $_SESSION['usuario_id'];
    $empresa = 2;
    $saldo = 0;

    $sql = "INSERT INTO cuentas 
    (
        Numero, Nombre, TipoSaldo, EmpresaId, Activo, FechaAlta, UsuarioAlta, Saldo
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $numero,
        $nombre,
        $tipo,
        $empresa,
        $activo,
        $fecha_alta,
        $usuarioAlta,
        $saldo
    ];

    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta.";
    } else {
        $stmt = $con->prepare($sql);
        if ($stmt) {
            $resultado = $stmt->execute($params); // Pasamos el array de parámetros

            $cuenta_Id = $con->lastInsertId();

            // Preparar inserción de subcuentas
            $sql_subcuenta = "INSERT INTO cuentas 
                (
                    CuentaPadreId, Numero, Nombre, TipoSaldo, EmpresaId, Activo, FechaAlta, UsuarioAlta, Saldo
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_subcuenta = $con->prepare($sql_subcuenta);

            foreach ($subcuenta_formateadas as $i => $subcuenta_numero) {
                $nombre = $nombre_subcuenta[$i] ?? '';
                $saldo = $saldo_subcuenta[$i] ?? 0;

                $stmt_subcuenta->execute([
                    $cuenta_Id,
                    $subcuenta_numero,
                    $nombre,
                    $tipo,
                    $empresa,
                    $activo,
                    $fecha_alta,
                    $usuarioAlta,
                    $saldo
                    
                ]);
            }



            if ($resultado) {
                echo "Cuenta guardada correctamente.";
            } else {
                echo "Error al guardar: " . implode(", ", $stmt->errorInfo());
            }
        } else {
            echo "Error al preparar la consulta: " . implode(", ", $con->errorInfo());
        }
    }
} else {
    echo "Faltan datos obligatorios.";
}
?>