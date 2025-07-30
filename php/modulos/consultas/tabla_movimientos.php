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
            p.Numero AS NumeroPoliza,
            b.Nombre AS NombreBeneficiario,
            CONCAT(c.Numero, ' - ', c.Nombre) AS Cuenta,
            pp.PolizaId,
            pp.Cargo AS Cargo,
            pp.Abono AS Abono,
            pp.Observaciones,
            pp.NumeroFactura
        FROM conta_partidaspolizas pp
        LEFT JOIN conta_polizas p ON pp.PolizaId = p.Id
        LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
        LEFT JOIN cuentas c ON pp.SubcuentaId = c.Id
        WHERE pp.ReferenciaId = :id
        AND c.Numero IN (123, 114)
        AND pp.EnKardex != 1
        LIMIT :inicio, :limite
    ");

    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $registrosPorPagina, PDO::PARAM_INT);
    $stmt->execute();

    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);



    //-------------------------------------------------------------------------------------
    $stmt2 = $con->prepare("
        SELECT 
            p.Numero AS NumeroPoliza,
            b.Nombre AS NombreBeneficiario,
            CONCAT(c.Numero, ' - ', c.Nombre) AS Cuenta,
            pp.PolizaId,
            pp.Cargo AS Cargo,
            pp.Abono AS Abono,
            pp.Observaciones,
            pp.NumeroFactura
        FROM conta_partidaspolizas pp
        LEFT JOIN conta_polizas p ON pp.PolizaId = p.Id
        LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
        LEFT JOIN cuentas c ON pp.SubcuentaId = c.Id
        WHERE pp.ReferenciaId = :id
        AND c.Numero NOT IN (123, 114)
        AND pp.EnKardex != 1
        LIMIT :inicio, :limite
    ");

    $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt2->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt2->bindValue(':limite', $registrosPorPagina, PDO::PARAM_INT);
    $stmt2->execute();

    $datos2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
}


?>
<link rel="stylesheet" href="../../../css/style.css">
<link rel="stylesheet" href="../../../css/style2.css">

<div class="card card-tabla mb-4">
    <div class="card-body">
        <div class="row">
            <!-- Tabla estática -->
            <div class="col-lg-6 mb-3">
                <h6 class="text-muted fw-bold">Cuenta de Gastos</h6>
                <table class="table table-hover tabla-mov1 table-bordered shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Póliza</th>
                            <th>Beneficiario</th>
                            <th>Cuenta</th>
                            <th>Cargo</th>
                            <th>Abono</th>
                            <th>Observaciones/Fact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($datos): ?>
                            <?php foreach ($datos as $fila): ?>
                                <tr class="small">
                                    <td>
                                        <a href="detalle_poliza.php?id=<?= $fila['PolizaId'] ?>">
                                            <?= $fila['NumeroPoliza'] ?>
                                        </a>
                                    </td>
                                    <td><?= $fila['NombreBeneficiario'] ?></td>
                                    <td><?= $fila['Cuenta'] ?></td>
                                    <td>$<?= $fila['Cargo'] ?></td>
                                    <td>$<?= $fila['Abono'] ?></td>
                                    <td><?= $fila['Observaciones'] . ' '. $fila['NumeroFactura'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No se encontraron registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Tabla dinámica -->
            <div class="col-lg-6 mb-3">
                <h6 class="text-muted fw-bold">Cuentas Operativo</h6>
                <table class="table table-hover tabla-mov2 table-bordered shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Póliza</th>
                            <th>Beneficiario</th>
                            <th>Cuenta</th>
                            <th>Cargo</th>
                            <th>Abono</th>
                            <th>Observaciones/Fact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($datos2): ?>
                            <?php foreach ($datos2 as $fila2): ?>
                                <tr class="small">
                                    <td>
                                        <a href="detalle_poliza.php?id=<?= $fila2['PolizaId'] ?>">
                                            <?= $fila2['NumeroPoliza'] ?>
                                        </a>
                                    </td>
                                    <td><?= $fila2['NombreBeneficiario'] ?></td>
                                    <td><?= $fila2['Cuenta'] ?></td>
                                    <td>$<?= $fila2['Cargo'] ?></td>
                                    <td>$<?= $fila2['Abono'] ?></td>
                                    <td><?= $fila2['Observaciones'] . ' '. $fila['NumeroFactura']?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No se encontraron registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>