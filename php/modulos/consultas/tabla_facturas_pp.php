<?php
include_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Número de registros por página
$registrosPorPagina = 20;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$wherePrincipal = [];
$paramsPrincipal = [];

$whereCount = [];
$paramsCount = [];

// Filtros comunes para ambas consultas (fecha y subcuenta)
if (!empty($_GET['fecha_desde'])) {
    $wherePrincipal[] = "p.Fecha >= :fecha_desde";
    $whereCount[] = "p.Fecha >= :fecha_desde";
    $paramsPrincipal[':fecha_desde'] = $_GET['fecha_desde'] . ' 00:00:00';
    $paramsCount[':fecha_desde'] = $_GET['fecha_desde'] . ' 00:00:00';
}
if (!empty($_GET['fecha_hasta'])) {
    $wherePrincipal[] = "p.Fecha <= :fecha_hasta";
    $whereCount[] = "p.Fecha <= :fecha_hasta";
    $paramsPrincipal[':fecha_hasta'] = $_GET['fecha_hasta'] . ' 23:59:59';
    $paramsCount[':fecha_hasta'] = $_GET['fecha_hasta'] . ' 23:59:59';
}
if (isset($_GET['subcuenta']) && is_numeric($_GET['subcuenta']) && (int) $_GET['subcuenta'] > 0) {
    $wherePrincipal[] = "p.Id IN (
        SELECT PolizaId
        FROM conta_partidaspolizas
        WHERE (PolizaId, Partida) IN (
            SELECT PolizaId, MAX(Partida)
            FROM conta_partidaspolizas
            GROUP BY PolizaId
        )
        AND SubcuentaId = :subcuenta
    )";
    $whereCount[] = "p.Id IN (
        SELECT PolizaId
        FROM conta_partidaspolizas
        WHERE (PolizaId, Partida) IN (
            SELECT PolizaId, MAX(Partida)
            FROM conta_partidaspolizas
            GROUP BY PolizaId
        )
        AND SubcuentaId = :subcuenta
    )";
    $paramsPrincipal[':subcuenta'] = (int) $_GET['subcuenta'];
    $paramsCount[':subcuenta'] = (int) $_GET['subcuenta'];
}

// **Filtro que sólo aplica en la consulta principal porque usa alias `pp`**
$wherePrincipal[] = "pp.Pagada = 0";

$whereSqlPrincipal = count($wherePrincipal) > 0 ? 'WHERE ' . implode(' AND ', $wherePrincipal) : '';
$whereSqlCount = count($whereCount) > 0 ? 'WHERE ' . implode(' AND ', $whereCount) : '';

$sql = "
SELECT
    pp.Partida AS Id,
    pp.Cargo,
    pp.Observaciones AS FacturaObservaciones,
    pp.NumeroFactura AS Factura,
    p.Id AS PolizaId,
    p.Numero AS PolizaNumero,
    p.FechaAlta AS FechaHora,
    r.Numero AS ReferenciaNumero,
    b.Nombre AS BeneficiarioNombre,
    cu.Numero AS SubcuentaNumero,
    cu.Nombre AS SubcuentaNombre,
    
    -- Subcuenta de la última partida de la póliza
    (
        SELECT SubcuentaId
        FROM conta_partidaspolizas pp2
        WHERE pp2.PolizaId = pp.PolizaId
        ORDER BY pp2.Partida DESC
        LIMIT 1
    ) AS UltimaSubcuentaId

FROM conta_partidaspolizas pp
LEFT JOIN conta_polizas p ON p.Id = pp.PolizaId
LEFT JOIN beneficiarios b ON b.Id = p.BeneficiarioId
LEFT JOIN cuentas cu ON cu.Id = pp.SubcuentaId
INNER JOIN conta_referencias r ON r.Id = pp.ReferenciaId
LEFT JOIN 2201aduanas a ON a.id2201aduanas = r.AduanaId
$whereSqlPrincipal
ORDER BY p.Fecha DESC, pp.Partida ASC

";


$stmt = $con->prepare($sql);
foreach ($paramsPrincipal as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $paramType);
}
$stmt->execute();
$poliza = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlCount = "SELECT COUNT(*) 
             FROM conta_polizas p
             LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
             $whereSqlCount";


?>
<div id="tabla-pp-wrapper">
    <table id="tabla-pp-container" class="table table-hover tabla-pp-container">
        <thead class="small">
            <tr>
                <th scope="col">
                    <input type="checkbox" id="select-all" class="form-check-input" aria-label="Seleccionar todas subcuentas">
                </th>
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
                                value="<?php echo $poliza['Id']; ?>" 
                                data-id="<?php echo $poliza['Id']; ?>"
                                data-cargo="<?php echo $poliza['Cargo']; ?>" 
                                data-ultimasubcuenta="<?php echo $poliza['UltimaSubcuentaId']; ?>"
                                aria-label="Checkbox for following text input">
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
                        <td><?php echo date("d/m/Y", strtotime($poliza['FechaHora'])); ?></td>
                        <td><?php echo $poliza['Factura']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">No se encontraron registros</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<!-- Paginación 
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
    </nav>-->
</div>

<script>
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'select-all') {
        const checkboxes = document.querySelectorAll('.chk-registro');
        checkboxes.forEach(cb => {
            if (!cb.disabled) {
                cb.checked = e.target.checked;
            }
        });
        actualizarTotalCargo();
        actualizarEstadoCheckboxesYBoton();
    }
});

</script>
