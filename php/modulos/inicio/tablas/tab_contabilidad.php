<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ./portal_web/Contabilidad/login.php');
    exit;
}
date_default_timezone_set('America/Mexico_City');
include('../../conexion.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$usuario = $_SESSION['usuario_id'];

$sql = "SELECT 
            r.Numero,
            r.Pedimentos,
            r.ClienteExportadorId,
            r.ClienteLogisticoId,
            r.SuReferencia,
            r.RecintoId,
            r.FechaContabilidad,
            r.FechaKardex,
            ce.nombreCorto_exportador AS Exportador,
            cl.nombreCorto_exportador AS Logistico,
            rf.nombre_conocido_recinto AS Recinto
        FROM conta_referencias r
        LEFT JOIN 01clientes_exportadores ce ON ce.id01clientes_exportadores = r.ClienteExportadorId
        LEFT JOIN 01clientes_exportadores cl ON cl.id01clientes_exportadores = r.ClienteLogisticoId
        LEFT JOIN 2206_recintos_fiscalizados rf ON rf.id2206_recintos_fiscalizados = r.RecintoId
        WHERE r.UsuarioAlta = :usuario AND r.Status = 2";

$stmt = $con->prepare($sql);
$stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table id="miTablaContabilidad" class="table table-striped table-bordered table-hover table-sm">
    <thead>
        <tr>
            <th>Número</th>
            <th>Pedimento</th>
            <th>Exportador</th>
            <th>Logístico</th>
            <th>Terminal</th>
            <th>Ref. Externa</th>
            <th>Días Contabilidad</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && count($result) > 0): ?>
            <?php foreach ($result as $row):
                $fechaInicio = new DateTime($row['FechaContabilidad']);
                $fechaFin = !empty($row['FechaKardex']) ? new DateTime($row['FechaKardex']) : new DateTime();
                $diasContabilidad = $fechaInicio->diff($fechaFin)->days;
                if ($diasContabilidad < 1) $diasContabilidad = 1;
            ?>
            <tr>
                <td><?= htmlspecialchars($row['Numero'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['Pedimentos'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['Exportador'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['Logistico'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['Recinto'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['SuReferencia'] ?? '') ?></td>
                <td><?= $diasContabilidad ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted" style="font-style: italic;">
                    No hay datos para mostrar
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
