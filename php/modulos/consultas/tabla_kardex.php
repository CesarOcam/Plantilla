<?php
include_once(__DIR__ . '/../conexion.php');

$where = [];
$params = [];

// Filtro base
$where[] = "c.Status != 2"; 

// Status
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "c.Status = :status";
    $params[':status'] = $_GET['status'];
}

// Fechas
if (!empty($_GET['fecha_desde'])) {
    $where[] = "c.Fecha >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'];
}
if (!empty($_GET['fecha_hasta'])) {
    $where[] = "c.Fecha <= :fecha_hasta";
    $params[':fecha_hasta'] = $_GET['fecha_hasta'];
}

// Número
if (!empty($_GET['num'])) {
    $where[] = "c.NumCg LIKE :num";
    $params[':num'] = "%" . $_GET['num'] . "%";
}

// Aduana
if (!empty($_GET['aduana'])) {
    $where[] = "r.AduanaId = :aduana";
    $params[':aduana'] = (int)$_GET['aduana'];  // cast a int
}

// Referencia
if (!empty($_GET['referencia'])) {
    $where[] = "r.Numero LIKE :referencia";
    $params[':referencia'] = "%" . $_GET['referencia'] . "%";
}

// Logístico
if (!empty($_GET['logistico'])) {
    $where[] = "le.razonSocial_exportador LIKE :logistico";
    $params[':logistico'] = "%" . $_GET['logistico'] . "%";
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Consulta principal (sin LIMIT)
$sql = "
SELECT 
    c.Id,
    c.NumCg,
    r.Numero AS ReferenciaNumero,
    r.AduanaId,
    b.identificacion AS BuqueNombre,
    le.razonSocial_exportador AS LogisticoNombre,
    ee.razonSocial_exportador AS ExportadorNombre,
    c.Fecha,
    c.Booking,
    c.SuReferencia,
    c.Saldo
FROM conta_cuentas_kardex c
LEFT JOIN conta_referencias r 
    ON c.Referencia = r.Id
LEFT JOIN transporte b 
    ON c.Barco = b.idtransporte
LEFT JOIN 01clientes_exportadores le 
    ON c.Logistico = le.id01clientes_exportadores
LEFT JOIN 01clientes_exportadores ee 
    ON c.Exportador = ee.id01clientes_exportadores
LEFT JOIN 2201aduanas a 
    ON r.AduanaId = a.id2201aduanas
$whereSql
ORDER BY c.Fecha DESC
";

$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$kardex = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="max-height: 500px; overflow-y: auto;">
    <table class="table table-hover">
        <thead class="small">
            <tr>
                <th scope="col"></th>
                <th scope="col">Id</th>
                <th scope="col">NumCg</th>
                <th scope="col">Referencia</th>
                <th scope="col">Logistico</th>
                <th scope="col">Exportador</th>
                <th scope="col">Fecha</th>
                <th scope="col">Barco</th>
                <th scope="col">Booking</th>
                <th scope="col">SuReferencia</th>
                <th scope="col">Saldo</th>
            </tr>
        </thead>
        <tbody class="small">
            <?php if ($kardex): ?>
                <?php foreach ($kardex as $kardex): ?>  
                    <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas/detalle_kardex.php?id=<?php echo $kardex['Id']; ?>';}"
                        style="cursor: pointer;">
                        <th scope="row">
                            <input class="form-check-input mt-1 kardex-checkbox" type="checkbox"
                                value="<?php echo $kardex['Id']; ?>" aria-label="Checkbox for following text input">
                        </th>
                        <td><?php echo $kardex['Id']; ?></td>
                        <td><?php echo $kardex['NumCg']; ?></td>
                        <td><?php echo $kardex['ReferenciaNumero']; ?></td>
                        <td><?php echo $kardex['LogisticoNombre']; ?></td>
                        <td><?php echo $kardex['ExportadorNombre']; ?></td>
                        <td><?php echo $kardex['Fecha']; ?></td>
                        <td><?php echo $kardex['BuqueNombre']; ?></td>
                        <td><?php echo $kardex['Booking']; ?></td>
                        <td><?php echo $kardex['SuReferencia']; ?></td>
                        <td><?php echo number_format($kardex['Saldo'], 2, '.', ','); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">No se encontraron registros</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

