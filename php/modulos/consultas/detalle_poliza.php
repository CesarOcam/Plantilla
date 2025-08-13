<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');
    exit;
}
include_once(__DIR__ . '/../conexion.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmt = $con->prepare("
    SELECT 
        e.Nombre AS EmpresaNombre,
        '' AS tipo,
        b.Nombre AS BeneficiarioId,
        p.Fecha,
        p.Numero,
        p.Concepto,
        CONCAT_WS(' ', u.NombreUsuario, u.apePatUsuario, u.apeMatUsuario) AS UsuarioAlta,
        p.FechaAlta,
        CASE 
            WHEN p.Activo = 1 THEN 'EN TRÁFICO'
            ELSE 'INACTIVA'
        END AS Activo
    FROM conta_polizas p
    LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
    LEFT JOIN usuarios u ON p.UsuarioAlta = u.idusuarios
    LEFT JOIN empresas e ON p.EmpresaId = e.Id
    WHERE p.Id = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$poliza = $stmt->fetch(PDO::FETCH_ASSOC);


$tipoPoliza = '';
if ($poliza && isset($poliza['Numero'])) {
    $prefijo = strtoupper(substr($poliza['Numero'], 0, 1));
    switch ($prefijo) {
        case 'C':
            $tipoPoliza = 'CHEQUE';
            break;
        case 'I':
            $tipoPoliza = 'INGRESO';
            break;
        case 'D':
            $tipoPoliza = 'DIARIO';
            break;
        case 'E':
            $tipoPoliza = 'EGRESO';
            break;
        default:
            $tipoPoliza = 'DESCONOCIDO';
    }
}

$stmt = $con->prepare("
    SELECT 
        p.Partida AS Id,
        CONCAT(c.Numero, ' - ', c.Nombre) AS SubcuentaNombre,
        p.Cargo,
        p.Abono,
        p.Observaciones,
        p.NumeroFactura AS Factura,
        r.Numero AS ReferenciaNumero,
        CONCAT(u1.nombreUsuario, ' ', u1.apePatUsuario, ' ', u1.apeMatUsuario) AS usuarioNombre,         -- created_by
        CONCAT(u2.nombreUsuario, ' ', u2.apePatUsuario, ' ', u2.apeMatUsuario) AS usuarioSolicitudNombre -- UsuarioSolicitud
    FROM conta_partidaspolizas p
    LEFT JOIN usuarios u1 ON p.created_by = u1.idusuarios
    LEFT JOIN usuarios u2 ON p.UsuarioSolicitud = u2.idusuarios
    LEFT JOIN cuentas c ON p.SubcuentaId = c.Id
    LEFT JOIN conta_referencias r ON p.ReferenciaId = r.Id
    WHERE p.PolizaId = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pólizas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 después -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-lite/1.3.0/material.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Iconos Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">
</head>

<?php
include_once __DIR__ . '/../../../config.php';

include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Buques" method="POST">
            <div class="card-header formulario_clientes">
                <h5 class="mb-0">Póliza: <?php echo $poliza['Numero']; ?></h5>
                <div class="row">
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="EmpresaId" class="form-label text-muted small">EMPRESA:</label>
                        <input id="EmpresaId" name="EmpresaId" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['EmpresaNombre']; ?>"
                            readonly>
                    </div>
                    <div class="col-4 col-sm-4 d-flex flex-column mt-4">
                        <label for="tipo" class="form-label text-muted small">TIPO:</label>
                        <input id="tipo" name="tipo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $tipoPoliza; ?>" readonly>
                    </div>
                    <div class="col-4 col-sm-4 d-flex flex-column mt-4">
                        <label for="BeneficiarioId" class="form-label text-muted small">BENEFICIARIO:</label>
                        <input id="BeneficiarioId" name="BeneficiarioId" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['BeneficiarioId']; ?>"
                            readonly>
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="Fecha" class="form-label text-muted small">FECHA:</label>
                        <input id="Fecha" name="Fecha" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['Fecha']; ?>" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="Numero" class="form-label text-muted small">NO. PÓLIZA:</label>
                        <input id="Numero" name="Numero" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['Numero']; ?>" readonly>
                    </div>
                    <div class="col-4 col-sm-4 d-flex flex-column mt-4">
                        <label for="Concepto" class="form-label text-muted small">CONCEPTO:</label>
                        <input id="Concepto" name="Concepto" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['Concepto']; ?>" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="UsuarioAlta" class="form-label text-muted small">USUARIO ALTA:</label>
                        <input id="UsuarioAlta" name="UsuarioAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['UsuarioAlta']; ?>"
                            readonly>
                    </div>
                    <div class="col-4 col-sm-4 d-flex flex-column mt-4">
                        <label for="FechaAlta" class="form-label text-muted small">FECHA ALTA:</label>
                        <input id="FechaAlta" name="FechaAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['FechaAlta']; ?>" readonly>
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="Activo" class="form-label text-muted small">STATUS:</label>
                        <input id="Activo" name="Activo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $poliza['Activo']; ?>" readonly>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-12">
                        <table class="table table-bordered table-sm tabla-partidas-estilo" id="tabla-partidas">
                            <thead class="table-light">
                                <tr>
                                    <th>Subcuenta</th>
                                    <th class="text-center">Cargo</th>
                                    <th class="text-center">Abono</th>
                                    <th>Observaciones</th>
                                    <th>Referencia</th>
                                    <th>Solicitado por</th>
                                    <th>Aplicado por</th>
                                    <th>Factura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_cargo = 0;
                                $total_abono = 0;
                                ?>
                                <?php if (empty($partidas)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Sin subcuentas asociadas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($partidas as $fila): ?>
                                        <?php
                                        $total_cargo += $fila['Cargo'];
                                        $total_abono += $fila['Abono'];
                                        ?>
                                        <tr class="text-center">
                                            <td><?= htmlspecialchars($fila['SubcuentaNombre']) ?></td>
                                            <td><?= '$ ' . number_format($fila['Cargo'], 2) ?></td>
                                            <td><?= '$ ' . number_format($fila['Abono'], 2) ?></td>
                                            <td><?= htmlspecialchars($fila['Observaciones']) ?></td>
                                            <td><?= htmlspecialchars($fila['ReferenciaNumero'], 2) ?></td>
                                            <td><?= htmlspecialchars($fila['usuarioSolicitudNombre'], 2) ?></td>
                                            <td><?= htmlspecialchars($fila['usuarioNombre'], 2) ?></td>
                                            <td><?= htmlspecialchars($fila['Factura']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($partidas)): ?>
                                <tfoot style="background-color: #f1f1f1;" class="tfoot-total">
                                    <tr class="fw-bold text-center align-middle" style="height: 45px;">
                                        <td>Total</td>
                                        <td><?= '$ ' . number_format($total_cargo, 2) ?></td>
                                        <td><?= '$ ' . number_format($total_abono, 2) ?></td>
                                        <td colspan="5"></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">

                        <button type="button" class="btn btn-outline-secondary rounded-0" id="btn_cancelar_poliza"
                            data-id="<?= $id ?>">
                            Cancelar Póliza
                        </button>
                    </div>
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="button" class="btn btn-outline-danger rounded-0"
                            onclick="window.location.href='../../vistas/consultas/consulta_poliza.php'">Salir</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../../../js/actualizar/cancelar_Poliza.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>