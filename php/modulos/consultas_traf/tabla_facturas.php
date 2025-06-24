<?php
include_once(__DIR__ . '/../conexion.php');
$stmtFacturas = $con->prepare("SELECT * FROM facturas_registradas WHERE status != 2");
$stmtFacturas->execute();
$facturas = $stmtFacturas->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las referencias en tráfico y contabilidad
$stmt = $con->prepare("SELECT Id, Numero FROM referencias WHERE Numero IS NOT NULL AND Status IN (1, 2)");
$stmt->execute();
$referencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$facturasConSubcuentas = [];

foreach ($facturas as $factura) {
    $rfc = $factura['rfc_proveedor'] ?? null;
    $subcuentas = [];

    if ($rfc) {
        $stmt = $con->prepare("SELECT Id FROM beneficiarios WHERE Rfc = ?");
        $stmt->execute([$rfc]);
        $beneficiario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($beneficiario) {
            $beneficiarioId = $beneficiario['Id'];
            $stmt = $con->prepare("SELECT subcuenta_id FROM subcuentas_beneficiarios WHERE beneficiario_id = ?");
            $stmt->execute([$beneficiarioId]);
            $subcuentaIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($subcuentaIds)) {
                $placeholders = str_repeat('?,', count($subcuentaIds) - 1) . '?';
                $stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas WHERE Id IN ($placeholders)");
                $stmt->execute($subcuentaIds);
                $subcuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    // Asociar las subcuentas al registro de la factura
    $factura['subcuentas'] = $subcuentas;
    $factura['beneficiario_id'] = $beneficiarioId ?? null;
    $facturasConSubcuentas[] = $factura;
}
echo "<script>console.log(" . json_encode($facturasConSubcuentas) . ");</script>";
?>

<table id="contenedor-tabla-facturas" class="table table-hover tabla-facturas">
    <thead class="small">
        <tr>
            <th scope="col-id">Factura</th>
            <th scope="col-referencia">Referencia</th>
            <th scope="col-beneficiario">Subcuenta</th>
            <th scope="col-importe">RFC Proveedor</th>
            <th scope="col-fecha">Proveedor</th>
            <th scope="col-fecha">Cliente</th>
            <th scope="col-fecha">Fecha</th>
            <th scope="col-fecha">Importe</th>
            <th></th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if (!empty($facturas)): ?>
            <?php foreach ($facturasConSubcuentas as $factura): ?>
                <tr>
                    <input type="hidden" name="factura_id[]" value="<?= $factura['Id'] ?>">
                    <td><?= htmlspecialchars($factura['folio']) ?></td>
                    <td>
                        <select name="referencia_id[]" class="form-control referencia-select">
                            <option value="">Referencia</option>
                            <?php foreach ($referencias as $referencia): ?>
                                <option value="<?= htmlspecialchars($referencia['Id']) ?>"
                                    <?= ($referencia['Id'] == $factura['referencia_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($referencia['Numero']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="subcuentas[]" class="form-control subcuenta-select">
                            <option value="">Subcuenta</option>
                            <?php
                            // Ajustar $factura['subcuentas'] para esta fila (ya que antes usabas $subcuentas global)
                            $subcuentasFila = $factura['subcuentas'] ?? [];
                            $primerId = !empty($subcuentasFila) ? $subcuentasFila[0]['Id'] : null;
                            foreach ($subcuentasFila as $subcuenta): ?>
                                <option value="<?= htmlspecialchars($subcuenta['Id']) ?>" <?= ($subcuenta['Id'] == $primerId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subcuenta['Numero'] . ' - ' . $subcuenta['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>

                    <td class="text-center"><?= htmlspecialchars($factura['rfc_proveedor']) ?></td>
                    <td>
                        <?php if (!empty($factura['beneficiario_id'])): ?>
                            <a href="../../modulos/consultas_cat/detalle_beneficiarios.php?id=<?= urlencode($factura['beneficiario_id']) ?>"
                                style="color: blue;">
                                <?= htmlspecialchars($factura['proveedor']) ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($factura['proveedor']) ?>
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($factura['cliente']) ?></td>
                    <td><?= htmlspecialchars($factura['fecha']) ?></td>
                    <td class="text-start">
                        <?php
                        $importe = isset($factura['importe']) && $factura['importe'] !== ''
                            ? number_format((float) $factura['importe'], 2)
                            : '0.00';
                        echo '$ ' . $importe;
                        ?>
                    </td>
                    <td class="text-start">
                        <button type="button" class="btn btn-link p-0 btn-trash eliminar-factura"
                            data-id="<?= htmlspecialchars($factura['Id']) ?>" data-bs-toggle="tooltip" data-bs-placement="top"
                            title="Eliminar" style="color: #a19b9b; font-size: 1.5rem; cursor: pointer;">
                            <i class="fas fa-trash-alt"></i>
                        </button>

                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>