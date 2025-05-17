<?php
include_once(__DIR__ . '/../conexion.php');

// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; // Índice de inicio para la consulta

// Consulta para obtener las cuentas (solo 20 registros por página)
$stmt = $con->prepare("SELECT Numero, Nombre, Saldo FROM cuentas ORDER BY Numero LIMIT :inicio, :registrosPorPagina");
$stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindParam(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para contar el total de registros
$stmtTotal = $con->prepare("SELECT COUNT(*) FROM cuentas");
$stmtTotal->execute();
$totalRegistros = $stmtTotal->fetchColumn();

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Organizar cuentas y subcuentas
$cuentasOrdenadas = [];
$cuentasPrincipales = [];
$subcuentasAgrupadas = [];

// Clasificar cuentas principales y subcuentas
foreach ($cuentas as $cuenta) {
    if (strpos($cuenta['Numero'], '-') === false) {
        $cuentasPrincipales[$cuenta['Numero']] = $cuenta;
    } else {
        $partes = explode('-', $cuenta['Numero']);
        $padre = $partes[0];
        $subcuentasAgrupadas[$padre][] = $cuenta;
    }
}

// Ordenar cuentas principales
ksort($cuentasPrincipales);

// Agrupar cuentas y subcuentas
foreach ($cuentasPrincipales as $numero => $cuentaPrincipal) {
    $cuentasOrdenadas[] = $cuentaPrincipal;
    if (isset($subcuentasAgrupadas[$numero])) {
        usort($subcuentasAgrupadas[$numero], function ($a, $b) {
            return strcmp($a['Numero'], $b['Numero']);
        });
        foreach ($subcuentasAgrupadas[$numero] as $subcuenta) {
            $cuentasOrdenadas[] = $subcuenta;
        }
    }
}

// Calcular el bloque de páginas a mostrar (de 10 en 10)
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);

?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col" class="checkbox-column"></th>
            <th scope="col" class="cuenta-column">Cuenta</th>
            <th scope="col" class="subcuenta-column">Subcuenta</th>
            <th scope="col">Nombre</th>
            <th scope="col">Saldo</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($cuentasOrdenadas): ?>
            <?php foreach ($cuentasOrdenadas as $cuenta): ?>
                <?php
                    $esSubcuenta = strpos($cuenta['Numero'], '-') !== false;
                    $cuentaNumero = $esSubcuenta ? '' : $cuenta['Numero'];
                    $subcuentaNumero = $esSubcuenta ? $cuenta['Numero'] : '';
                    $sangria = $esSubcuenta ? 'ms-3' : ''; // Sangría para subcuentas
                ?>
                <tr>
                    <th scope="row">
                        <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox">
                    </th>
                    <td><?php echo $cuentaNumero; ?></td>
                    <td class="<?php echo $sangria; ?>">
                        <?php if ($esSubcuenta): ?>
                            <span class="material-icons align-middle" style="font-size: 16px; vertical-align: middle;">subdirectory_arrow_right</span>
                            <span class="ms-1"><?php echo $subcuentaNumero; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $cuenta['Nombre']; ?></td>
                    <td><?php echo $cuenta['Saldo']; ?></td>
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
