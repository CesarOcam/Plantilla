<?php
include_once(__DIR__ . '/../conexion.php');

// Consulta principal: sumar saldos por logístico sin límite (sin paginación)
$sql = "
SELECT 
    le.razonSocial_exportador AS LogisticoNombre,
    SUM(c.Saldo) AS SaldoTotal
FROM conta_cuentas_kardex c
LEFT JOIN 01clientes_exportadores le 
    ON c.Logistico = le.id01clientes_exportadores
GROUP BY c.Logistico, le.razonSocial_exportador
ORDER BY SaldoTotal DESC
";

$stmt = $con->prepare($sql);
$stmt->execute();
$cuentasPorLogistico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular el total general sumando todos los saldos
$totalGeneral = 0;
foreach ($cuentasPorLogistico as $cuenta) {
    $totalGeneral += $cuenta['SaldoTotal'];
}
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col">Logístico</th>
            <th scope="col" class="text-end">Saldo Total</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($cuentasPorLogistico): ?>
            <?php foreach ($cuentasPorLogistico as $cuenta): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cuenta['LogisticoNombre']); ?></td>
                    <td class="text-end"><?php echo number_format($cuenta['SaldoTotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <!-- Fila de total general -->
            <tr class="fw-bold">
                <td></td>
                <td class="text-end">
                    TOTAL : <span class="fw-bold">$<?php echo number_format($totalGeneral, 2); ?></span>
                </td>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="2" class="text-center">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>