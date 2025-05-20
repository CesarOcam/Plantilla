<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 15;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';
$filtroLike = '%' . $filtro . '%';

$sql = "SELECT Id, Nombre, Tipo, Rfc FROM beneficiarios";

if ($filtro !== '') {
    $sql .= " WHERE Nombre LIKE :filtro";
}

$sql .= " ORDER BY Id ASC LIMIT :inicio, :registrosPorPagina";

$stmt = $con->prepare($sql);

if ($filtro !== '') {
    $stmt->bindValue(':filtro', $filtroLike, PDO::PARAM_STR);
}

$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);

$stmt->execute();
$beneficiarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM beneficiarios");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

// Total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Bloque de páginas (de 10 en 10)
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col"></th>
            <th scope="col">Id</th>
            <th scope="col">Nombre</th>
            <th scope="col">Tipo</th>
            <th scope="col">RFC</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($beneficiarios): ?>
            <?php foreach ($beneficiarios as $beneficiario): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_beneficiarios.php?id=<?php echo $beneficiario['Id']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $beneficiario['Id']; ?></td>
                    <td><?php echo $beneficiario['Nombre']; ?></td>
                    <td>
                        <?php 
                            echo ($beneficiario['Tipo'] == 1) ? 'PHCA' : 
                                 (($beneficiario['Tipo'] == 2) ? 'Gastos Generales' : 'Otro');
                        ?>
                    </td>
                    <td><?php echo $beneficiario['Rfc']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No se encontraron registros</td></tr>
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
