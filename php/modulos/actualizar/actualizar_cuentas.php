<?php
include('../conexion.php');
session_start(); // Asegúrate de tener esto al inicio del archivo

// Verificar que los campos obligatorios estén presentes
if (isset($_POST['id_cuenta'], $_POST['numero'], $_POST['nombre'], $_POST['tipo'])) {
    $id_cuenta = (int) $_POST['id_cuenta'];
    $numero = $_POST['numero'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $usuarioModificacion = $_SESSION['usuario_id'];
    date_default_timezone_set('America/Mexico_City');
    $fecha_modificacion = date("Y-m-d H:i:s");


    $sqlUpdate = "UPDATE cuentas SET 
        Numero = ?, Nombre = ?, TipoSaldo = ?, FechaUltimaModificacion = ?, UsuarioUltimaModificacion = ?
        WHERE Id = ?";
    $params = [$numero, $nombre, $tipo, $fecha_modificacion, $usuarioModificacion, $id_cuenta];

    $stmt = $con->prepare($sqlUpdate);
    if (!$stmt->execute($params)) {
        echo "Error al actualizar la cuenta principal: " . implode(", ", $stmt->errorInfo());
        exit;
    }

    // Obtener el número de la cuenta padre
    $stmtPadre = $con->prepare("SELECT Numero FROM cuentas WHERE Id = ?");
    $stmtPadre->execute([$id_cuenta]);
    $cuentaPadre = $stmtPadre->fetch(PDO::FETCH_ASSOC);

    if (!$cuentaPadre) {
        echo "Cuenta padre no encontrada";
        exit;
    }

    $prefijo = $cuentaPadre['Numero']; // Prefijo dinámico según la cuenta padre

    if (isset($_POST['subcuentas_json'])) {
        $subcuentas = json_decode($_POST['subcuentas_json'], true);

        foreach ($subcuentas as $sub) {
            // Concatenar el prefijo con el número adicional que venga
            $numeroCompleto = $prefijo . '-' . $sub['numero']; // Ej: "216-001" o "215-100"

            $sqlInsert = "INSERT INTO cuentas 
            (CuentaPadreId, Numero, Nombre, Saldo, TipoSaldo, Activo, EmpresaId, FechaAlta, UsuarioAlta, created_at, updated_at) 
            VALUES 
            (:padre, :numero, :nombre, :saldo, :tipoSaldo, 1, 2, :fechaAlta, :usuarioAlta, :created, :updated)";

            $stmtSub = $con->prepare($sqlInsert);
            $stmtSub->execute([
                ':padre' => $id_cuenta,
                ':numero' => $numeroCompleto,
                ':nombre' => $sub['nombre'],
                ':saldo' => $sub['saldo'],
                ':tipoSaldo' => $tipo,
                ':fechaAlta' => $fecha_modificacion,
                ':usuarioAlta' => $usuarioModificacion,
                ':created' => $fecha_modificacion,
                ':updated' => $fecha_modificacion
            ]);
        }
    }


    echo "ok";
} else {
    echo "Faltan datos obligatorios.";
}
?>