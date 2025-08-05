<?php
include_once(__DIR__ . '/../conexion.php');
$registrosPorPagina = 20;

$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$where = [];
$params = [];
$where[] = "c.Status != 2"; // <--- Esta es la línea agregada

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "c.Status = :status";
    $params[':status'] = $_GET['status'];
}

if (!empty($_GET['fecha_desde'])) {
    $where[] = "c.Fecha >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'];
}

if (!empty($_GET['fecha_hasta'])) {
    $where[] = "c.Fecha <= :fecha_hasta";
    $params[':fecha_hasta'] = $_GET['fecha_hasta'];
}

if (!empty($_GET['num'])) {
    $where[] = "c.NumCg LIKE :num";
    $params[':num'] = "%" . $_GET['num'] . "%";
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT 
    c.Id,
    c.NumCg,
    r.Numero AS ReferenciaNumero,
    b.identificacion AS BuqueNombre,
    le.razonSocial_exportador AS LogisticoNombre,
    ee.razonSocial_exportador AS ExportadorNombre,
    c.Fecha,
    c.Booking,
    c.SuReferencia,
    c.Saldo
FROM conta_cuentas_kardex c
LEFT JOIN conta_referencias r ON c.Referencia = r.Id
LEFT JOIN transporte b ON c.Barco = b.idtransporte
LEFT JOIN 01clientes_exportadores le ON c.Logistico = le.id01clientes_exportadores
LEFT JOIN 01clientes_exportadores ee ON c.Exportador = ee.id01clientes_exportadores
$whereSql
ORDER BY c.Fecha DESC
LIMIT :inicio, :registrosPorPagina
";



$stmt = $con->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);

$stmt->execute();
$kardex = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener total de registros con el mismo filtro
$sqlTotal = "SELECT COUNT(*) FROM conta_cuentas_kardex c $whereSql";
$stmtTotal = $con->prepare($sqlTotal);

foreach ($params as $key => $value) {
    $stmtTotal->bindValue($key, $value);
}

$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

// Calcular total de páginas y bloque de paginación
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

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
                    <td><?php echo $kardex['Saldo']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" class="text-center">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Paginación -->
<nav aria-label="Page navigation example" class="d-flex justify-content-center">
    <ul class="pagination">
        <li class="page-item <?php echo ($paginaActual == 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>

        <?php for ($i = $inicioBloque; $i <= $finBloque; $i++): ?>
            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($paginaActual == $totalPaginas) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>