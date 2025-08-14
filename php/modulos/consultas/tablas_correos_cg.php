<?php
include('../conexion.php');

header('Content-Type: text/html; charset=utf-8');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p>Error: ID no proporcionado</p>";
    exit;
}

// Paso 1: Obtener ClienteLogisticoId desde conta_referencias
$sqlRef = "SELECT ClienteLogisticoId FROM conta_referencias WHERE Id = ?";
$stmtRef = $con->prepare($sqlRef);
$stmtRef->execute([$id]);
$refData = $stmtRef->fetch(PDO::FETCH_ASSOC);

if (!$refData || empty($refData['ClienteLogisticoId'])) {
    echo "<p>No se encontró Cliente Logístico para esta referencia.</p>";
    exit;
}

$clienteLogisticoId = $refData['ClienteLogisticoId'];

//CORREOS LOGÍSTICOS
$sqlLogisticos = "
    SELECT idcorreos_01clientes_exportadores, correo FROM correos_01clientes_exportadores
    WHERE tipo_correo = 3 AND id01clientes_exportadores = ?
";

$stmtLogisticos = $con->prepare($sqlLogisticos);
$stmtLogisticos->execute([$clienteLogisticoId]);
$logisticos = $stmtLogisticos->fetchAll(PDO::FETCH_ASSOC);


$sqlUsuarios = "
    SELECT login, email 
    FROM sec_users
    WHERE idDepartamento = 3
";

$stmtUsuarios = $con->prepare($sqlUsuarios);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);


// ARCHIVOS
$sqlArchivos = "SELECT Nombre FROM conta_referencias_archivos WHERE Referencia_id = ?";
$stmtArchivos = $con->prepare(query: $sqlArchivos);
$stmtArchivos->execute([$id]);
$archivos = $stmtArchivos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-4">
        <table class="table table-bordered table-sm tabla-archivos">
            <thead>
                <tr>
                    <th>Archivos para enviar (<?= count($archivos) ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($archivos)): ?>
                    <?php foreach ($archivos as $archivo): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($archivo['Nombre']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>No hay archivos asociados</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="col-md-4">
        <table class="table table-bordered table-sm tabla-correos">
            <thead>
                <tr>
                    <th></th>
                    <th>Emails Logísticos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logisticos as $logistico): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="mails_logistico[]"
                                value="<?= htmlspecialchars($logistico['correo']) ?>"
                                class="form-check-input big-checkbox check-correo" style="cursor:pointer;">
                        </td>
                        <td><?= htmlspecialchars($logistico['correo'] ?? 'Sin email') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-4">
        <table class="table table-bordered table-sm tabla-correos">
            <thead>
                <tr>
                    <th></th>
                    <th>Emails Amexport</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="checkbox" name="mail_main" value="jesus.reyes@grupomexport.com"
                            class="form-check-input big-checkbox check-correo" style="cursor:pointer;" checked disabled>
                    </td>
                    <td>jesus.reyes@grupomexport.com</td>
                </tr>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="mails_amex[]"
                                value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                class="form-check-input big-checkbox check-correo" style="cursor:pointer;">
                        </td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>