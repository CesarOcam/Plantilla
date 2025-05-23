<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; // Índice de inicio para la consulta

// Consulta para obtener los clientes (solo 20 registros por página)
/*$stmt = $con->prepare("SELECT Id, Numero, Concepto, BeneficiarioId, Importe, EmpresaId, Fecha, Activo FROM polizas LIMIT :inicio, :registrosPorPagina");
$stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindParam(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$poliza = $stmt->fetchAll(PDO::FETCH_ASSOC);*/
$where = [];
$params = [];

// STATUS: Solo aplicar si es 1 o 0 (no vacío)
if (isset($_GET['status']) && ($_GET['status'] === '0' || $_GET['status'] === '1')) {
    $where[] = "r.Status = :status";
    $params[':status'] = $_GET['status'];
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
    $where[] = "r.Numero LIKE :referencia";
    $params[':referencia'] = "%" . $_GET['referencia'] . "%";
}

// LOGISTICO
if (!empty($_GET['logistico'])) {
    $where[] = "log.razonSocial_exportador LIKE :logistico";
    $params[':logistico'] = "%" . $_GET['logistico'] . "%";
}



// WHERE final
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// CONSULTA FINAL CON JOIN Y PAGINACIÓN
$sql = "SELECT 
    r.Id, r.Numero, r.ClienteLogisticoId, r.ClienteExportadorId, r.Status, r.FechaAlta, r.FechaContabilidad,
    exp.razonSocial_exportador AS ExportadorNombre,
    log.razonSocial_exportador AS LogisticoNombre 
    FROM referencias r
    LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
    LEFT JOIN 01clientes_exportadores log ON r.ClienteLogisticoId = log.id01clientes_exportadores
    $whereSql
    ORDER BY r.FechaAlta DESC
    LIMIT :inicio, :registrosPorPagina";


$stmt = $con->prepare($sql);

// Parámetros dinámicos
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Parámetros de paginación
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);

$stmt->execute();
$referencia = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Consulta para contar el total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM polizas");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Calcular el bloque de páginas a mostrar (de 10 en 10)
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

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
        <?php if ($referencia): ?>
            <?php foreach ($referencia as $referencia): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas/detalle_poliza.php?id=<?php echo $referencia['Id']; ?>';}"
                    style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value=""
                            aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $referencia['Id']; ?></td>
                    <td><?php echo $referencia['Numero']; ?></td>
                    <td><?php echo $referencia['LogisticoNombre']; ?></td>
                    <td><?php echo $referencia['ExportadorNombre']; ?></td>
                    <td>
                        <?php
                        if ($referencia['Status'] == 1) {
                            echo '<span style="color: rgba(0, 128, 0, 0.6);">ACTIVA</span>';
                        } elseif ($referencia['Status'] == 0) {
                            echo '<span style="color: rgba(255, 0, 0, 0.6);">INACTIVA</span>';
                        } else {
                            echo '<span>Otro</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo $referencia['FechaAlta']; ?></td>
                    <td><?php echo $referencia['FechaContabilidad']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No se encontraron registros</td>
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