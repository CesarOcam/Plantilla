<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; 

$where = [];
$params = [];

// STATUS: Solo aplicar si es 1 o 0 (no vacío)
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "p.Activo = :status";
    $params[':status'] = $_GET['status'];
}

// FECHA DESDE
if (!empty($_GET['fecha_desde'])) {
    $where[] = "p.Fecha >= :fecha_desde";
    $params[':fecha_desde'] = $_GET['fecha_desde'];
}

// FECHA HASTA
if (!empty($_GET['fecha_hasta'])) {
    $where[] = "p.Fecha <= :fecha_hasta";
    $params[':fecha_hasta'] = $_GET['fecha_hasta'];
}

// PÓLIZA
if (!empty($_GET['poliza'])) {
    $where[] = "p.Numero LIKE :poliza";
    $params[':poliza'] = "%" . $_GET['poliza'] . "%";
}

// BENEFICIARIO
if (!empty($_GET['beneficiario'])) {
    $where[] = "b.Nombre LIKE :beneficiario";
    $params[':beneficiario'] = "%" . $_GET['beneficiario'] . "%";
}

// WHERE final
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// CONSULTA FINAL CON JOIN Y PAGINACIÓN
$sql = "SELECT 
            p.Id, p.Numero, p.Concepto, p.Importe, p.EmpresaId, p.FechaAlta, p.Activo, 
            b.Nombre AS BeneficiarioNombre 
        FROM conta_polizas p
        LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
        $whereSql
        ORDER BY p.Id DESC
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
$poliza = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para contar el total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM conta_polizas");
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
            <th scope="col">Poliza</th>
            <th scope="col">Concepto</th>
            <th scope="col">Beneficiario</th>
            <th scope="col">Importe</th>
            <th scope="col">Empresa</th>
            <th scope="col">Fecha</th>
            <th scope="col">Status</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($poliza): ?>
            <?php foreach ($poliza as $poliza): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas/detalle_poliza.php?id=<?php echo $poliza['Id']; ?>';}"
                    style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value=""
                            aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $poliza['Id'] ?? ''; ?></td>
                    <td><?php echo $poliza['Numero'] ?? ''; ?></td>
                    <td><?php echo $poliza['Concepto'] ?? ''; ?></td>
                    <td><?php echo $poliza['BeneficiarioNombre'] ?? ''; ?></td>
                    <td><?php echo !empty($poliza['Importe']) ? number_format($poliza['Importe'], 2, '.', ',') : ''; ?></td>
                    <td>
                        <?php
                        echo ($poliza['EmpresaId'] == 1) ? 'Amexport' :
                            (($poliza['EmpresaId'] == 2) ? 'Amexport Logística' : 'Otro');
                        ?>
                    </td>
                    <td><?php echo date("Y-m-d", strtotime($poliza['FechaAlta'])); ?></td>
                    <td>
                        <?php
                        if ($poliza['Activo'] == 1) {
                            echo '<span>ACTIVA</span>';
                        } elseif ($poliza['Activo'] == 0) {
                            echo '<span>INACTIVA</span>';
                        } else {
                            echo '<span>Otro</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td class="text-center" colspan="9">No se encontraron registros</td>
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

