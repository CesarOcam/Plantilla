<?php
include('../conexion.php');

if (isset($_POST['nombre'], $_POST['tipo'])) {
    $nombre = trim($_POST['nombre']);
    $tipo = trim($_POST['tipo']);
    $rfc = trim($_POST['rfc'] ?? '');
    $subcuentas = $_POST['subcuentas'] ?? [];

    if (count($subcuentas) == 0) {
        echo "Debes seleccionar al menos una subcuenta.";
        exit;
    }

    // Primer subcuenta para SubcuentaDefaultId
    $subcuentaDefaultId = array_shift($subcuentas); // Extrae el primer elemento y lo elimina del array

    function obtenerFechaHoraActual() {
        return date("Y-m-d H:i:s");
    }

    $fecha_alta = obtenerFechaHoraActual();
    $activo = 1;
    $usuarioAlta = 1;

    // Insertar el buque / beneficiario
    $sql = "INSERT INTO beneficiarios (SubcuentaDefaultId, Nombre, Tipo, Rfc, Activo, FechaAlta, UsuarioAlta)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $subcuentaDefaultId,
        $nombre,
        $tipo,
        $rfc,
        $activo,
        $fecha_alta,
        $usuarioAlta
    ];

    if (count($params) !== substr_count($sql, '?')) {
        echo "Error: El número de parámetros no coincide con los tokens `?` en la consulta.";
        exit;
    }

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        echo "Error al preparar la consulta: " . implode(", ", $con->errorInfo());
        exit;
    }

    $resultado = $stmt->execute($params);

    if (!$resultado) {
        echo "Error al guardar: " . implode(", ", $stmt->errorInfo());
        exit;
    }

    // Obtener ID del registro insertado (del beneficiario / buque)
    $idBeneficiario = $con->lastInsertId();

    // Insertar las subcuentas restantes en subcuentasbeneficiarios
    if (count($subcuentas) > 0) {
        $sqlSubcuentas = "INSERT INTO subcuentasbeneficiarios (BeneficiarioId, SubcuentaId) VALUES (?, ?)";
        $stmtSub = $con->prepare($sqlSubcuentas);

        foreach ($subcuentas as $idSubcuenta) {
            $stmtSub->execute([$idBeneficiario, $idSubcuenta]);
        }
    }

    echo "Beneficiario guardado correctamente.";
} else {
    echo "Faltan datos obligatorios.";
}
?>

