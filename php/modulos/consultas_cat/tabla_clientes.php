<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; // Índice de inicio para la consulta

// Consulta para obtener los clientes (solo 20 registros por página)
$stmt = $con->prepare("SELECT id01clientes_exportadores, tipoClienteExportador, nombreCorto_exportador, logistico_asociado, rfc_exportador, tipo_cliente, statusEcomienda_exportador FROM 01clientes_exportadores LIMIT :inicio, :registrosPorPagina");
$stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindParam(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para contar el total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM 01clientes_exportadores");
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
            <th scope="col">Persona</th>
            <th scope="col">Cliente</th>
            <th scope="col">Logístico</th>
            <th scope="col">RFC</th>
            <th scope="col">Tipo</th>
            <th scope="col">Encomienda</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($clientes): ?>
            <?php foreach ($clientes as $cliente): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_clientes.php?id=<?php echo $cliente['id01clientes_exportadores']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo $cliente['id01clientes_exportadores']; ?></td>
                    <td>
                        <?php
                        echo ($cliente['tipoClienteExportador'] == 1) ? 'FÍSICA' :
                            (($cliente['tipoClienteExportador'] == 2) ? 'MORAL' : 'Otro');
                        ?>
                    </td>
                    <td><?php echo $cliente['nombreCorto_exportador']; ?></td>
                    <td>
                        <?php 
                            if ($cliente['logistico_asociado']) {
                                $logisticoStmt = $con->prepare("SELECT nombreCorto_exportador FROM 01clientes_exportadores WHERE id01clientes_exportadores = ?");
                                $logisticoStmt->execute([$cliente['logistico_asociado']]);
                                $logistico = $logisticoStmt->fetch(PDO::FETCH_ASSOC);
                                echo $logistico ? $logistico['nombreCorto_exportador'] : 'No disponible';
                            } else {
                                echo 'No asociado';
                            }
                        ?>
                    </td>
                    <td><?php echo $cliente['rfc_exportador']; ?></td>
                    <td>
                        <?php
                        echo ($cliente['tipo_cliente'] == 0) ? 'EXPORTADOR' :
                            (($cliente['tipo_cliente'] == 1) ? 'LOGÍSTICO' :
                                (($cliente['tipo_cliente'] == 2) ? 'EXPORTADOR - LOGÍSTICO' : 'Otro'));
                        ?>
                    </td>
                    <td>
                        <?php
                        echo ($cliente['statusEcomienda_exportador'] == 1) ? 'SI' :
                            (($cliente['statusEcomienda_exportador'] == 2) ? 'NO' :
                                (($cliente['statusEcomienda_exportador'] == 3) ? 'SI, CON VIGENCIA' : 'Otro'));
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
