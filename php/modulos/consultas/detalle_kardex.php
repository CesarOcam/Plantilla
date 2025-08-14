<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}

include_once(__DIR__ . '/../conexion.php'); // Ajusta el path según sea necesario

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

// Consulta para la póliza
$sql = "
SELECT 
    c.Id,
    c.NumCg,
    r.Numero AS ReferenciaNumero,
    r.Id AS ReferenciaId,
    b.identificacion AS BuqueNombre,
    le.razonSocial_exportador AS LogisticoNombre,
    ee.razonSocial_exportador AS ExportadorNombre,
    c.Fecha,
    c.Booking,
    c.SuReferencia,
    c.Importe,
    c.Anticipos,
    c.Saldo,
    p.Id AS PolizaId,
    p.Numero AS PolizaNum,
    CASE
        WHEN c.Status = 1 THEN 'VIGENTE'
        ELSE 'VENCIDA'
    END AS Status,
    c.Fecha,
    u.name AS UsuarioNombreCompleto
FROM conta_cuentas_kardex c
LEFT JOIN conta_referencias r ON c.Referencia = r.Id
LEFT JOIN transporte b ON c.Barco = b.idtransporte
LEFT JOIN conta_polizas p ON c.Poliza_id = p.Id
LEFT JOIN 01clientes_exportadores le ON c.Logistico = le.id01clientes_exportadores
LEFT JOIN 01clientes_exportadores ee ON c.Exportador = ee.id01clientes_exportadores
LEFT JOIN sec_users u ON c.Created_by = u.login
WHERE c.Id = :id
";

$stmt = $con->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$kardex = $stmt->fetch(PDO::FETCH_ASSOC);

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
        <form id="form_Kardex" method="POST">
            <div class="card-header formulario_clientes">
                <h5 class="mb-0">Información <?php echo $kardex['NumCg']; ?></h5> 
                <div class="row">
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="EmpresaId" class="form-label text-muted small">NUMCG:</label>
                        <input id="EmpresaId" name="EmpresaId" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['NumCg']; ?>" readonly>
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="tipo" class="form-label text-muted small">REFERENCIA:</label>
                        <a href="../consultas/detalle_referencia.php?id=<?php echo $kardex['ReferenciaId']; ?>"
                            target="_self" style="color: blue; text-decoration: none;">
                            <input id="tipo" name="tipo" type="text"
                                class="form-control input-transparent border-0 border-bottom rounded-0"
                                style="background-color: transparent; cursor: pointer; color: blue;"
                                value="<?php echo $kardex['ReferenciaNumero']; ?>" readonly>
                        </a>
                    </div>
                    <div class="col-4 col-sm-4 d-flex flex-column mt-4">
                        <label for="BeneficiarioId" class="form-label text-muted small">EXPORTADOR:</label>
                        <input id="BeneficiarioId" name="BeneficiarioId" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['ExportadorNombre']; ?>"
                            readonly>
                    </div>
                    <div class="col-4 col-sm-4 d-flex flex-column mt-4">
                        <label for="Fecha" class="form-label text-muted small">LOGÍSTICO:</label>
                        <input id="Fecha" name="Fecha" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['LogisticoNombre']; ?>"
                            readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="Numero" class="form-label text-muted small">BUQUE:</label>
                        <input id="Numero" name="Numero" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['BuqueNombre']; ?>"
                            readonly>
                    </div>
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="Concepto" class="form-label text-muted small">BOOKING:</label>
                        <input id="Concepto" name="Concepto" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['Booking']; ?>" readonly>
                    </div>
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="UsuarioAlta" class="form-label text-muted small">SUREFERENCIA:</label>
                        <input id="UsuarioAlta" name="UsuarioAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['SuReferencia']; ?>"
                            readonly>
                    </div>
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="FechaAlta" class="form-label text-muted small">IMPORTE:</label>
                        <input id="FechaAlta" name="FechaAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo '$ ' . $kardex['Importe']; ?>"
                            readonly>
                    </div>
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="Activo" class="form-label text-muted small">ANTICIPOS:</label>
                        <input id="Activo" name="Activo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo '$ ' . $kardex['Anticipos']; ?>"
                            readonly>
                    </div>
                    <div class="col-2 col-sm-2 d-flex flex-column mt-4">
                        <label for="Activo" class="form-label text-muted small">SALDO:</label>
                        <input id="Activo" name="Activo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo '$ ' . $kardex['Saldo']; ?>"
                            readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="Activo" class="form-label text-muted small">FECHA CREACION:</label>
                        <input id="Activo" name="Activo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['Fecha']; ?>" readonly>
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="Activo" class="form-label text-muted small">CREADO POR:</label>
                        <input id="Activo" name="Activo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;"
                            value="<?php echo $kardex['UsuarioNombreCompleto']; ?>" readonly>
                    </div>
                    <div class="col-4 col-sm-2 d-flex flex-column mt-4">
                        <label for="poliza" class="form-label text-muted small">POLIZA GENERADA:</label>
                        <a href="../consultas/detalle_poliza.php?id=<?php echo $kardex['PolizaId']; ?>"
                            target="_self" style="color: blue; text-decoration: none;">
                            <input id="poliza" name="poliza" type="text"
                                class="form-control input-transparent border-0 border-bottom rounded-0"
                                style="background-color: transparent; cursor: pointer; color: blue;"
                                value="<?php echo $kardex['PolizaNum']; ?>" readonly>
                        </a>
                    </div>
                    <div class="col-4 col-sm-1 d-flex flex-column mt-4">
                        <label for="Activo" class="form-label text-muted small">STATUS:</label>
                        <input id="Activo" name="Activo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $kardex['Status']; ?>" readonly>
                    </div>
                </div>

                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="button" class="btn btn-outline-danger rounded-0"
                            onclick="window.location.href='../../vistas/consultas/consulta_kardex.php'">Salir</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>