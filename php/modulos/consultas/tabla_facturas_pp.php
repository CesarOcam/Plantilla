<?php
include_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$where = [];
$params = [];

// Siempre sólo pólizas no pagadas
$where[] = "p.Pagada = 0";

// Filtros de fechas
if (!empty($_GET['fecha_desde'])) {
    $where[] = "p.Fecha >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'] . ' 00:00:00';
}
if (!empty($_GET['fecha_hasta'])) {
    $where[] = "p.Fecha <= :fecha_hasta";
    $params[':fecha_hasta'] = $_GET['fecha_hasta'] . ' 23:59:59';
}

// Filtro por subcuenta en última partida de la póliza
if (isset($_GET['subcuenta']) && is_numeric($_GET['subcuenta']) && (int) $_GET['subcuenta'] > 0) {
    $where[] = "p.Id IN (
        SELECT PolizaId
        FROM partidaspolizas
        WHERE (PolizaId, Partida) IN (
            SELECT PolizaId, MAX(Partida)
            FROM partidaspolizas
            GROUP BY PolizaId
        )
        AND SubcuentaId = :subcuenta
    )";
    $params[':subcuenta'] = (int) $_GET['subcuenta'];
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$inicio = (int) ($inicio ?? 0);
$registrosPorPagina = (int) ($registrosPorPagina ?? 20);

$sql = "
SELECT
    pp.Partida AS Id,
    pp.Cargo,
    pp.Observaciones AS FacturaObservaciones,
    p.Id AS PolizaId,
    p.Numero AS PolizaNumero,
    p.FechaAlta AS FechaHora,
    r.Numero AS ReferenciaNumero,
    b.Nombre AS BeneficiarioNombre,
    cu.Numero AS SubcuentaNumero,
    cu.Nombre AS SubcuentaNombre
FROM partidaspolizas pp
LEFT JOIN polizas p ON p.Id = pp.PolizaId
LEFT JOIN beneficiarios b ON b.Id = p.BeneficiarioId
LEFT JOIN cuentas cu ON cu.Id = pp.SubcuentaId
INNER JOIN referencias r ON r.Id = pp.ReferenciaId
LEFT JOIN 2201aduanas a ON a.id2201aduanas = r.AduanaId
$whereSql
ORDER BY p.Fecha DESC, pp.Partida ASC
LIMIT $inicio, $registrosPorPagina
";

// Preparar y ejecutar
$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $paramType);
}
$stmt->execute();
$poliza = $stmt->fetchAll(PDO::FETCH_ASSOC);



$sqlCount = "SELECT COUNT(*) 
             FROM polizas p
             LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
             $whereSql";
$stmtTotal = $con->prepare($sqlCount);

foreach ($params as $key => $value) {
    $stmtTotal->bindValue($key, $value);
}
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

// Calcular total de páginas y bloque de navegación
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table id="tabla-pp-container" class="table table-hover tabla-pp-container">
    <thead class="small">
        <tr>
            <th scope="col"></th>
            <th scope="col">Poliza</th>
            <th scope="col">Referencia</th>
            <th scope="col">Beneficiario</th>
            <th scope="col">Num. Subcuenta</th>
            <th scope="col">Subcuenta</th>
            <th scope="col">Cargo</th>
            <th scope="col">Fecha/Hora</th>
            <th scope="col">Factura</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($poliza): ?>
            <?php foreach ($poliza as $poliza): ?>
                <tr onclick="if(event.target.type === 'checkbox'){}">
                    <th scope="row">
                        <input class="form-check-input mt-1 chk-registro" type="checkbox"
                            value="<?php echo $poliza['Id']; ?>" data-id="<?php echo $poliza['Id']; ?>"
                            data-cargo="<?php echo $poliza['Cargo']; ?>" aria-label="Checkbox for following text input">
                    </th>
                    <td style="cursor: pointer;">
                        <a href="../../modulos/consultas/detalle_poliza.php?id=<?php echo $poliza['PolizaId']; ?>"
                            class="text-primary">
                            <?php echo $poliza['PolizaNumero']; ?>
                        </a>
                    </td>
                    <td><?php echo $poliza['ReferenciaNumero']; ?></td>
                    <td><?php echo $poliza['BeneficiarioNombre']; ?></td>
                    <td><?php echo $poliza['SubcuentaNumero']; ?></td>
                    <td><?php echo $poliza['SubcuentaNombre']; ?></td>
                    <td><?php echo '$ ' . number_format($poliza['Cargo'], 2); ?></td>
                    <td><?php echo $poliza['FechaHora']; ?></td>
                    <td><?php echo $poliza['FacturaObservaciones']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" class="text-center">No se encontraron registros</td>
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