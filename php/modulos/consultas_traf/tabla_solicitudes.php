<?php
//TABLA DEL MODAL DE PAGO SOLICITUDES

include_once(__DIR__ . '/../conexion.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$stmt = $con->prepare("
    SELECT Id, Numero, Nombre
    FROM cuentas
    WHERE Activo = 1
    AND EmpresaId = 2
    AND (
        (SUBSTRING_INDEX(Numero, '-', 1) = '216' AND Numero LIKE '216-%') OR
        (SUBSTRING_INDEX(Numero, '-', 1) = '113' AND Numero LIKE '113-%')
    )
    ORDER BY Nombre
");

$stmt->execute();
$subcuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$registrosPorPagina = 4;

$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$sql = "SELECT 
            s.Id, 
            s.EmpresaId, 
            e.Nombre AS EmpresaNombre,
            s.BeneficiarioId, 
            b.Nombre AS BeneficiarioNombre,
            s.Importe, 
            s.Fecha
        FROM conta_solicitudes s
        LEFT JOIN empresas e ON s.EmpresaId = e.Id
        LEFT JOIN beneficiarios b ON s.BeneficiarioId = b.Id
        WHERE s.Status = 1
        ORDER BY s.Fecha DESC
        LIMIT :inicio, :registrosPorPagina";

$stmt = $con->prepare($sql);
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtTotal = $con->prepare("SELECT COUNT(*) FROM conta_solicitudes WHERE Status = 1");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
?>

<table class="table table-hover tabla-solicitudes">
    <thead class="small">
        <tr>
            <th scope="col-id">No.</th>
            <th scope="col-empresa">EmpresaId</th>
            <th scope="col-beneficiario">BeneficiarioId</th>
            <th scope="col-importe">Importe</th>
            <th scope="col-fecha">Fecha/Hora</th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($solicitudes): ?>
            <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                    <td><?php echo htmlspecialchars($solicitud['Id']); ?></td>
                    <td><?php echo htmlspecialchars($solicitud['EmpresaNombre']); ?></td>
                    <td><?php echo htmlspecialchars($solicitud['BeneficiarioNombre']); ?></td>
                    <td><?php echo '$' . htmlspecialchars(number_format($solicitud['Importe'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($solicitud['Fecha']); ?></td>
                    <td>
                        <button type="button" class="btn btn-link p-0 btn-aceptar" data-id="<?php echo $solicitud['Id']; ?>"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Aceptar"
                            style="color:rgb(105, 177, 108); font-size: 1.5rem; cursor: pointer;">
                            <i class="fas fa-check-circle"></i>
                        </button>
                    </td>
                    <td>
                        <button type="button" class="btn btn-link p-0 btn-trash" data-id="<?php echo $solicitud['Id']; ?>"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar"
                            style="color: #a19b9b; font-size: 1.5rem; cursor: pointer;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Inputs ocultos fuera de la tabla para evitar descuadre -->
<div style="display:none;">
    <?php if ($solicitudes): ?>
        <?php foreach ($solicitudes as $solicitud): ?>
            <input type="hidden" class="referencia-id-hidden" id="referencia_<?php echo $solicitud['SolicitudFacturaId']; ?>" value="<?php echo htmlspecialchars($solicitud['ReferenciaId']); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<!-- Paginación -->
<nav aria-label="Page navigation example" class="d-flex justify-content-center">
    <ul class="pagination">
        <li class="page-item <?php echo ($paginaActual == 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-pagina="<?php echo max(1, $paginaActual - 1); ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>

        <?php for ($i = $inicioBloque; $i <= $finBloque; $i++): ?>
            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                <a class="page-link" href="#" data-pagina="<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?php echo ($paginaActual == $totalPaginas) ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-pagina="<?php echo min($totalPaginas, $paginaActual + 1); ?>"
                aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>

<script>
    window.subcuentas = <?php echo json_encode($subcuentas); ?>;
</script>
<!--Script para obtener los datos relacionados a la solicitud-->
<script src="../../../js/consultar_Solicitudes_tabla.js"></script>
<script src="../../../js/eliminar/eliminar_solicitudes_tabla.js"></script>