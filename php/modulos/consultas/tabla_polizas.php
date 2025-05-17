<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; // Índice de inicio para la consulta

// Consulta para obtener los clientes (solo 20 registros por página)
$stmt = $con->prepare("SELECT Id, Numero, Concepto, BeneficiarioId, Importe, EmpresaId, Fecha, Activo FROM polizas LIMIT :inicio, :registrosPorPagina");
$stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindParam(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$poliza = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <th scope="col">Poliza</th>
            <th scope="col">Concepto</th>
            <th scope="col">Beneficiario</th>
            <th scope="col">Importe</th>
            <th scope="col">Empresa</th>
            <th scope="col">Fecha</th>
            <th scope="col">Activo</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($poliza): ?>
            <?php foreach ($poliza as $poliza): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas/detalle_poliza.php?id=<?php echo $poliza['Id']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $poliza['Id']; ?></td>
                    <td><?php echo $poliza['Numero']; ?></td>
                    <td><?php echo $poliza['Concepto']; ?></td>
                    <td><?php echo $poliza['BeneficiarioId']; ?></td>
                    <td><?php echo $poliza['Importe']; ?></td>
                    <td>
                        <?php
                        echo ($poliza['EmpresaId'] == 1) ? 'Amexport' :
                                (($poliza['EmpresaId'] == 2) ? 'Amexport Logística'  : 'Otro');
                        ?>
                    </td>
                    <td><?php echo $poliza['Fecha']; ?></td>
                    <td>
                        <?php
                        echo ($poliza['Activo'] == 1) ? 'Activa' :
                                (($poliza['Activo'] == 0) ? 'Inactiva'  : 'Otro');
                        ?>
                    </td>
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
