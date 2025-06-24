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
        p.Importe AS ImportePoliza,
        p.Fecha AS FechaPoliza,
        b.Nombre AS NombreBeneficiario,
        c.Numero AS NumeroSubcuenta,
        c.Nombre AS NombreSubcuenta,
        r.Numero AS NumeroReferencia
    FROM partidaspolizas pp
    LEFT JOIN polizas p ON pp.PolizaId = p.Id
    LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
    LEFT JOIN cuentas c ON pp.SubcuentaId = c.Id
    LEFT JOIN referencias r ON pp.ReferenciaId = r.Id
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

<div class="card card-tabla mb-4">
    <div class="card-body">
        <div class="row">
            <!-- Tabla estática -->
            <div class="col-lg-6 mb-3">
                <h6 class="text-muted fw-bold">PÓLIZAS RELACIONADAS</h6>
                <table class="table table-hover tabla-movimientos-1 table-bordered shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Póliza</th>
                            <th>Beneficiario</th>
                            <th>Importe</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($datos): ?>
                            <?php foreach ($datos as $fila): ?>
                                <tr  class="small">
                                    <td>
                                        <a href="detalle_poliza.php?id=<?= $fila['PolizaId'] ?>">
                                            <?= $fila['NumeroPoliza'] ?>
                                        </a>
                                    </td>
                                    <td><?= $fila['NombreBeneficiario']?></td>
                                    <td>$<?= $fila['ImportePoliza'] ?></td>
                                    <td><?= $fila['FechaPoliza'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tabla dinámica -->
            <div class="col-lg-6 mb-3">
                <h6 class="text-muted fw-bold">CUENTA DE GASTOS</h6>
                <table class="table table-hover tabla-movimientos-2 table-bordered shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Subcuenta</th>
                            <th>Cargo</th>
                            <th>Abono</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($datos): ?>
                            <?php foreach ($datos as $fila): ?>
                                <tr  class="small">
                                    <td><?= $fila['NumeroSubcuenta'] . '-' . $fila['NombreSubcuenta'] ?></td>
                                    <td>$<?= $fila['Cargo'] ?></td>
                                    <td>$<?= $fila['Abono'] ?></td>
                                    <td><?= $fila['Observaciones'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>