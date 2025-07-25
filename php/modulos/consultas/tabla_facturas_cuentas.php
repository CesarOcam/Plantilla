<?php
include_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$registrosPorPagina = 20;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

// Consulta principal: partir de cuentas, LEFT JOIN partidaspolizas filtrando partidas no pagadas
$sql = "
SELECT
    cu.Id,
    cu.Numero AS SubcuentaNumero,
    cu.Nombre AS SubcuentaNombre,
    
    -- Total abonado a esta cuenta 216-xxx (aún no pagado)
    COALESCE((
        SELECT SUM(pp1.Abono)
        FROM partidaspolizas pp1
        WHERE pp1.SubcuentaId = cu.Id AND pp1.Pagada = 1
    ), 0) AS TotalAbonado,

    -- Total cargado relacionado con las mismas pólizas o referencias que los abonos a esta cuenta
    COALESCE((
        SELECT SUM(pp2.Cargo)
        FROM partidaspolizas pp2
        WHERE pp2.Pagada = 0
          AND pp2.SubcuentaId != cu.Id
          AND (
              pp2.PolizaId IN (
                  SELECT PolizaId
                  FROM partidaspolizas
                  WHERE SubcuentaId = cu.Id AND Pagada = 0
              )
              OR
              pp2.ReferenciaId IN (
                  SELECT ReferenciaId
                  FROM partidaspolizas
                  WHERE SubcuentaId = cu.Id AND Pagada = 0
              )
          )
    ), 0) AS TotalRelacionado

FROM cuentas cu
WHERE cu.CuentaPadreId = 21
  AND cu.Numero LIKE '216-%'
ORDER BY (TotalRelacionado - TotalAbonado) DESC, cu.Numero ASC
LIMIT :inicio, :registrosPorPagina
";

$stmt = $con->prepare($sql);
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$polizas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($polizas as &$poliza) {
    $totalAbonado = floatval($poliza['TotalAbonado']);
    $totalRelacionado = floatval($poliza['TotalRelacionado']);
    $poliza['SaldoPendiente'] = $totalRelacionado - $totalAbonado;
}
unset($poliza); // buena práctica por si acaso


// Consulta para contar total de subcuentas hijas para paginación
$sqlCount = "
SELECT COUNT(*)
FROM cuentas cu
WHERE cu.CuentaPadreId = 21
  AND cu.Numero LIKE '216-%' 
";

$stmtCount = $con->prepare($sqlCount);
$stmtCount->execute();
$totalRegistros = $stmtCount->fetchColumn();

$totalPaginas = ceil($totalRegistros / $registrosPorPagina);
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table id="tabla-pp-container" class="table table-hover tabla-pp-container">
    <thead class="small">
        <tr>
            <th scope="col">Num.Subcuenta</th>
            <th scope="col">Subcuenta</th>
            <th scope="col">Cargo Total</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($polizas): ?>
            <?php foreach ($polizas as $poliza): ?>
                <tr>
                    <td><?php echo htmlspecialchars($poliza['SubcuentaNumero']); ?></td>
                    <td><?php echo htmlspecialchars($poliza['SubcuentaNombre']); ?></td>
                    <td><?php echo '$ ' . number_format($poliza['SaldoPendiente'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="text-center">No se encontraron registros</td>
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