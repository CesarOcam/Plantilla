<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 15;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';
$filtroLike = '%' . $filtro . '%';

$sql = "SELECT id2221_recintos, inmueble_recintos, aduana_recintos FROM 2221_recintos";

if ($filtro !== '') {
    $sql .= " WHERE inmueble_recintos LIKE :filtro";
}

$sql .= " LIMIT :inicio, :registrosPorPagina";

$stmt = $con->prepare($sql);

if ($filtro !== '') {
    $stmt->bindValue(':filtro', $filtroLike, PDO::PARAM_STR);
}

$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);

$stmt->execute();
$recintos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Consulta para contar el total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM 2221_recintos");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Calcular el bloque de páginas (de 10 en 10)
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col"></th>
            <th scope="col">Id</th>
            <th scope="col">Nombre</th>
            <th scope="col">Aduana</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($recintos): ?>
            <?php foreach ($recintos as $recinto): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_recintos.php?id=<?php echo $recinto['id2221_recintos']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $recinto['id2221_recintos']; ?></td>
                    <td><?php echo $recinto['inmueble_recintos']; ?></td>
                    <td><?php echo $recinto['aduana_recintos']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Paginación -->
<nav aria-label="Page navigation example" class="d-flex justify-content-center">
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
