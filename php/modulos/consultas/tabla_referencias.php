<?php
include_once(__DIR__ . '/../conexion.php');

$where = [];
$params = [];

// STATUS: Solo aplicar si está entre 1 y 4
if (isset($_GET['status']) && in_array($_GET['status'], ['1', '2', '3', '4'])) {
    $where[] = "r.Status = :status";
    $params[':status'] = $_GET['status'];
}

// ADUANA
if (!empty($_GET['aduana']) && $_GET['aduana'] !== 'todas') {
    $where[] = "r.AduanaId = :aduana";
    $params[':aduana'] = $_GET['aduana'];
}

// FECHA DESDE
if (!empty($_GET['fecha_desde'])) {
    $where[] = "r.FechaAlta >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'];
}

// FECHA HASTA (incluir todo el día)
if (!empty($_GET['fecha_hasta'])) {
    $fechaHasta = date('Y-m-d', strtotime($_GET['fecha_hasta'] . ' +1 day'));
    $where[] = "r.FechaAlta < :fecha_hasta";
    $params[':fecha_hasta'] = $fechaHasta;
}

// PÓLIZA
if (!empty($_GET['referencia'])) {
    $referenciaInput = trim($_GET['referencia']);           // eliminar espacios al inicio y final
    $referenciaInput = preg_replace('/\s+/', '%', $referenciaInput); // reemplazar espacios internos por %
    $where[] = "r.Numero LIKE :referencia";
    $params[':referencia'] = "%" . $referenciaInput . "%";  // envolver con % para LIKE
}

// LOGISTICO
if (!empty($_GET['logistico'])) {
    $logisticoInput = trim($_GET['logistico']);             // eliminar espacios al inicio y final
    $logisticoInput = preg_replace('/\s+/', '%', $logisticoInput);   // reemplazar espacios internos por %
    $where[] = "r.ClienteLogisticoId = :logistico";
    $params[':logistico'] = $logisticoInput;
}

// WHERE final
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT 
    r.Id, r.Numero, r.ClienteLogisticoId, r.ClienteExportadorId, r.Status, r.FechaAlta, r.FechaContabilidad,r.FechaKardex,
    exp.razonSocial_exportador AS ExportadorNombre,
    log.razonSocial_exportador AS LogisticoNombre 
    FROM conta_referencias r
    LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
    LEFT JOIN 01clientes_exportadores log ON r.ClienteLogisticoId = log.id01clientes_exportadores
    $whereSql
    ORDER BY r.FechaAlta DESC";

$stmt = $con->prepare($sql);

// Parámetros dinámicos
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$referencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Contenedor con scroll -->
<div style="max-height: 600px; overflow-y: auto;">
    <table class="table table-hover">
        <thead class="small">
            <tr>
                <th scope="col"></th>
                <th scope="col">Id</th>
                <th scope="col">Referencia</th>
                <th scope="col">Logístico</th>
                <th scope="col">Exportador</th>
                <th scope="col">Status</th>
                <th scope="col">Apertura</th>
                <th scope="col">Conta</th>
                <th scope="col">Kardex</th>
            </tr>
        </thead>
        <tbody class="small">
            <?php if ($referencias): ?>
                <?php foreach ($referencias as $referencia): ?>
                    <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas/detalle_referencia.php?id=<?php echo $referencia['Id']; ?>';}" style="cursor: pointer;">
                        <th scope="row">
                        </th>
                        <td><?php echo $referencia['Id']; ?></td>
                        <td><?php echo $referencia['Numero']; ?></td>
                        <td><?php echo $referencia['LogisticoNombre']; ?></td>
                        <td><?php echo $referencia['ExportadorNombre']; ?></td>
                        <td>
                            <?php
                            switch ($referencia['Status']) {
                                case 1: echo '<span>EN TRÁFICO</span>'; break;
                                case 2: echo '<span>EN CONTABILIDAD</span>'; break;
                                case 3: echo '<span>FACTURADA</span>'; break;
                                case 4: echo '<span>CANCELADA</span>'; break;
                                default: echo '<span>DESCONOCIDO</span>'; break;
                            }
                            ?>
                        </td>
                        <td><?php echo $referencia['FechaAlta']; ?></td>
                        <td><?php echo $referencia['FechaContabilidad']; ?></td>
                        <td><?php echo $referencia['FechaKardex']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td class="text-center" colspan="9">No se encontraron registros</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
