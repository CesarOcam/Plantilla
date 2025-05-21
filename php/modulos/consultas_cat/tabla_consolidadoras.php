<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 15;

// Determinar página actual
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';
$filtroLike = '%' . $filtro . '%';

$sql = "SELECT id_consolidadora, denominacion_consolidadora FROM consolidadoras WHERE status_consolidadora = 1";

if ($filtro !== '') {
    $sql .= " AND denominacion_consolidadora LIKE :filtro";
}

$sql .= " LIMIT :inicio, :registrosPorPagina";

$stmt = $con->prepare($sql);

if ($filtro !== '') {
    $stmt->bindValue(':filtro', $filtroLike, PDO::PARAM_STR);
}

$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);

$stmt->execute();
$consolidadora = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Obtener total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM consolidadoras");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Bloques de paginación (de 10 en 10)
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col"></th>
            <th scope="col">Id</th>
            <th scope="col">Nombre</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($consolidadora): ?>
            <?php foreach ($consolidadora as $row): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_consolidadoras.php?id=<?php echo $row['id_consolidadora']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1 chkConsolidadora" type="checkbox" value="<?php echo $row['id_consolidadora']; ?>" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $row['id_consolidadora']; ?></td>
                    <td><?php echo $row['denominacion_consolidadora']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">No se encontraron registros</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Paginación -->
<nav aria-label="Page navigation" class="d-flex justify-content-center">
    <ul class="pagination">
        <li class="page-item <?php echo ($paginaActual == 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>

        <?php for ($i = $inicioBloque; $i <= $finBloque; $i++): ?>
            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($paginaActual == $totalPaginas) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
