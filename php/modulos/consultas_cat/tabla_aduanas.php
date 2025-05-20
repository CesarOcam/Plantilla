<?php
include_once(__DIR__ . '/../conexion.php');

// Configuración de paginación
$registrosPorPagina = 15;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';
$filtroLike = '%' . $filtro . '%';

$sql = "SELECT 
            id2201aduanas, 
            aduana_aduana, 
            seccion_aduana, 
            nombre_corto_aduana, 
            denominacion_aduana, 
            prefix_aduana, 
            tipoAduana, 
            status_aduana 
        FROM 2201aduanas";

if ($filtro !== '') {
    $sql .= " WHERE nombre_corto_aduana LIKE :filtro";
}

$sql .= " ORDER BY status_aduana DESC 
          LIMIT :inicio, :limite";

$stmt = $con->prepare($sql);

if ($filtro !== '') {
    $stmt->bindValue(':filtro', $filtroLike, PDO::PARAM_STR);
}

$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':limite', $registrosPorPagina, PDO::PARAM_INT);

$stmt->execute();
$aduanas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM 2201aduanas");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Cálculo de bloques de 10 páginas
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col"></th>
            <th scope="col">Id</th>
            <th scope="col">Aduana Sección</th>
            <th scope="col">Nombre Corto</th>
            <th scope="col">Denominación</th>
            <th scope="col">Prefijo</th>
            <th scope="col">Tipo Aduana</th>
            <th scope="col">Status</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($aduanas): ?>
            <?php foreach ($aduanas as $row): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_aduanas.php?id=<?php echo $row['id2201aduanas']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $row['id2201aduanas']; ?></td>
                    <td><?php echo $row['aduana_aduana'] . '-' . $row['seccion_aduana']; ?></td>
                    <td><?php echo $row['nombre_corto_aduana']; ?></td>
                    <td><?php echo $row['denominacion_aduana']; ?></td>
                    <td><?php echo $row['prefix_aduana']; ?></td>
                    <td>
                        <?php
                            echo ($row['tipoAduana'] == 'M') ? 'Marítimo' :
                                 (($row['tipoAduana'] == 'T') ? 'Terrestre' :
                                 (($row['tipoAduana'] == 'A') ? 'Aéreo' : 'Otro'));
                        ?>
                    </td>
                    <td>
                        <?php if ($row['status_aduana'] == 1): ?>
                            <span style="color: rgba(0, 128, 0, 0.6);">ACTIVO</span>  <!-- verde con opacidad -->
                        <?php elseif ($row['status_aduana'] == 0): ?>
                            <span style="color: rgba(255, 0, 0, 0.6);">INACTIVO</span> <!-- rojo con opacidad -->
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">No se encontraron registros</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Paginación -->
<nav aria-label="Page navigation" class="d-flex justify-content-center">
    <ul class="pagination">
        <li class="page-item <?php echo ($paginaActual <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>" aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>

        <?php for ($i = $inicioBloque; $i <= $finBloque; $i++): ?>
            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($paginaActual >= $totalPaginas) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>" aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
