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

$usuario = $_POST['usuario'] ?? $_SESSION['usuario_id']; // Si no se envía, usar sesión

$sql = "SELECT 
            r.Id,
            r.Numero,
            r.Pedimentos,
            r.ClienteExportadorId,
            r.ClienteLogisticoId,
            r.SuReferencia,
            r.RecintoId,
            r.FechaAlta,
            r.FechaContabilidad,
            ce.nombreCorto_exportador AS Exportador,
            cl.nombreCorto_exportador AS Logistico,
            rf.nombre_conocido_recinto AS Recinto
        FROM conta_referencias r
        LEFT JOIN 01clientes_exportadores ce ON ce.id01clientes_exportadores = r.ClienteExportadorId
        LEFT JOIN 01clientes_exportadores cl ON cl.id01clientes_exportadores = r.ClienteLogisticoId
        LEFT JOIN 2206_recintos_fiscalizados rf ON rf.id2206_recintos_fiscalizados = r.RecintoId
        WHERE r.UsuarioAlta = :usuario AND r.Status = 1";

$stmt = $con->prepare($sql);
$stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);

if (!$stmt->execute()) {
    echo '<table class="table table-sm"><tr><td colspan="6" class="text-center text-muted" style="font-style: italic;">Error al ejecutar la consulta</td></tr></table>';
    exit;
}

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculamos los días en tráfico para cada registro
foreach ($result as &$row) {
    $fechaAlta = !empty($row['FechaAlta']) ? new DateTime($row['FechaAlta']) : new DateTime();
    $fechaFin = !empty($row['FechaContabilidad']) ? new DateTime($row['FechaContabilidad']) : new DateTime();
    $diasEnTrafico = $fechaAlta->diff($fechaFin)->days;
    if ($diasEnTrafico < 1) $diasEnTrafico = 1;
    $row['DiasEnTrafico'] = $diasEnTrafico;
}
unset($row); // rompe la referencia

// Ordenamos de mayor a menor según DiasEnTrafico
usort($result, function($a, $b) {
    return $b['DiasEnTrafico'] <=> $a['DiasEnTrafico'];
});
?>

<table id="miTablaTrafico" class="table table-striped table-bordered table-hover table-sm">
    <thead>
        <tr>
            <th>Número</th>
            <th>Pedimento</th>
            <th>Exportador</th>
            <th>Logístico</th>
            <th>Terminal</th>
            <th>Ref. Externa</th>
            <th>Días Tráfico</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && count($result) > 0): ?>
            <?php foreach ($result as $row): ?>
                <tr>
                    <td>
                        <a href="../modulos/consultas/detalle_referencia.php?id=<?= urlencode($row['Id']) ?>">
                            <?= htmlspecialchars($row['Numero'] ?? '') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($row['Pedimentos'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Exportador'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Logistico'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Recinto'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['SuReferencia'] ?? '') ?></td>
                    <td><?= $row['DiasEnTrafico'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
