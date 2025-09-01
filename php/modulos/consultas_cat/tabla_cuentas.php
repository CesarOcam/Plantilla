<?php
include_once(__DIR__ . '/../conexion.php');

$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';
$filtroLike = '%' . $filtro . '%';

$sql = "SELECT Id, Numero, Nombre, Saldo FROM cuentas WHERE Activo = 1";

if ($filtro !== '') {
    $sql .= " AND (Nombre LIKE :filtro OR Numero LIKE :filtro)";
}

$sql .= " ORDER BY Numero";

$stmt = $con->prepare($sql);

if ($filtro !== '') {
    $stmt->bindValue(':filtro', $filtroLike, PDO::PARAM_STR);
}

$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col" class="checkbox-column"></th>
            <th scope="col" class="cuenta-column">Cuenta</th>
            <th scope="col" class="subcuenta-column">Subcuenta</th>
            <th scope="col">Nombre</th>
            <th scope="col">Saldo</th>
            <th>Editar</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($cuentasOrdenadas): ?>
            <?php foreach ($cuentasOrdenadas as $cuenta): ?>
                <?php
                    $esSubcuenta = strpos($cuenta['Numero'], '-') !== false;
                    $cuentaNumero = $esSubcuenta ? '' : $cuenta['Numero'];
                    $subcuentaNumero = $esSubcuenta ? $cuenta['Numero'] : '';
                    $sangria = $esSubcuenta ? 'ms-3' : '';
                ?>
                <tr>
                    <th scope="row"></th>
                    <td><?php echo $cuentaNumero; ?></td>
                    <td class="<?php echo $sangria; ?>">
                        <?php if ($esSubcuenta): ?>
                            <span class="material-icons align-middle" style="font-size: 16px; vertical-align: middle;">subdirectory_arrow_right</span>
                            <span class="ms-1"><?php echo $subcuentaNumero; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $cuenta['Nombre']; ?></td>
                    <td><?php echo '$ '.$cuenta['Saldo']; ?></td>
                    <td>
                        <?php if (!$esSubcuenta): ?>
                            <a href="../../modulos/consultas_cat/detalle_cuentas.php?id=<?php echo $cuenta['Id']; ?>" class="text-decoration-none">
                                <span class="material-icons" style="font-size: 28px; vertical-align: middle; color: #b0b0b0;">edit</span>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No se encontraron registros</td></tr>
        <?php endif; ?>
    </tbody>
</table>
