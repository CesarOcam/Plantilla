<?php
include_once(__DIR__ . '/../conexion.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; // Índice de inicio para la consulta

try {
    $stmt = $con->prepare("
        SELECT 
            pp.*, 
            p.Numero AS NumeroPoliza,
            c.Numero AS NumeroSubcuenta,
            c.Nombre AS NombreSubcuenta
        FROM partidaspolizas pp
        LEFT JOIN polizas p ON pp.PolizaId = p.Id
        LEFT JOIN cuentas c ON pp.SubcuentaId = c.Id
        WHERE pp.ReferenciaId = :id
        LIMIT :inicio, :limite
    ");
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $registrosPorPagina, PDO::PARAM_INT);
    $stmt->execute();

    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
}
?>

<table id="tabla-movimientos" class="table table-hover tabla-movimientos">
    <thead class="small">
        <tr>
            <th scope="col">Póliza</th>
            <th scope="col">Empresa</th>
            <th scope="col">Subcuenta</th>
            <th scope="col">Importe</th>
            <th scope="col">Observaciones</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($datos): ?>
            <?php foreach ($datos as $datos): ?>
                <tr>
                    <td><?php echo $datos['NumeroPoliza']; ?></td>
                    <td>AMEXPORT LOGÍSTICA</td>
                    <td><?php echo $datos['NumeroSubcuenta'] . '-' . $datos['NombreSubcuenta']; ?></td>
                    <td><?php echo '$'.$datos['Cargo']; ?></td>
                    <td><?php echo $datos['Observaciones']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
