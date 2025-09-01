<?php
include_once(__DIR__ . '/../conexion.php');

$idCuentaPadre = $_GET['id'] ?? null;

if (!$idCuentaPadre) {
    echo "ID de la cuenta padre no proporcionado";
    exit;
}

$sql = "SELECT Id, Numero, Nombre, Saldo FROM cuentas WHERE Activo = 1 AND CuentaPadreId = :idPadre ORDER BY Numero";
$stmt = $con->prepare($sql);
$stmt->bindValue(':idPadre', $idCuentaPadre, PDO::PARAM_INT);
$stmt->execute();
$subcuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtrar solo subcuentas con formato xxx-xxx
$subcuentas = array_filter($subcuentas, function($sub) {
    return preg_match('/^\d{1,}-\d{1,}$/', $sub['Numero']);
});


?>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6>Agregar Subcuenta</h6>
    <button type="button" id="btnAgregarSubcuenta" class="btn btn-sm btn-primary">Agregar nueva</button>
</div>

<table id="tablaSubcuentas" class="table table-hover tabla-subcuentas-detalle">
    <thead>
        <tr>
            <th>Subcuenta</th>
            <th>Nombre</th>
            <th>Saldo</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($subcuentas): ?>
            <?php foreach ($subcuentas as $sub): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sub['Numero']); ?></td>
                    <td><?php echo htmlspecialchars($sub['Nombre']); ?></td>
                    <td><?php echo '$ ' . number_format($sub['Saldo'], 2); ?></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">No hay subcuentas para esta cuenta</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="../../../js/agregar_Subcuenta.js"></script>
