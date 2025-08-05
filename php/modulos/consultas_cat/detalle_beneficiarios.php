<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

// 1. Consulta principal del beneficiario
$stmt = $con->prepare("
    SELECT 
        b.Nombre, b.Tipo, b.Rfc, b.Activo, b.FechaAlta, b.UsuarioAlta,
        CONCAT_WS(' ', u.NombreUsuario, u.apePatUsuario, u.apeMatUsuario) AS NombreUsuarioAlta
    FROM beneficiarios b
    LEFT JOIN usuarios u ON b.UsuarioAlta = u.idusuarios
    WHERE b.Id = :id
");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$beneficiario = $stmt->fetch(PDO::FETCH_ASSOC);

// Subcuentas asignadas
$stmtSub = $con->prepare("
    SELECT subcuenta_id
    FROM conta_subcuentas_beneficiarios
    WHERE beneficiario_id = :id
");
$stmtSub->bindParam(':id', $id, PDO::PARAM_INT);
$stmtSub->execute();
$subcuentaIds = $stmtSub->fetchAll(PDO::FETCH_COLUMN); // array plano con los IDs seleccionados

// Obtener subcuentas en select
$stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas WHERE CuentaPadreId IS NOT NULL");
$stmt->execute();
$subcuenta = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Buques</title>
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

    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">
</head>

<?php
include_once __DIR__ . '/../../../config.php';

include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Beneficiarios" method="POST">
            <div class="card-header formulario_buques">
                <h5>Información de Beneficiario</h5>
                <div class="row">
                    <div class="col-10 col-sm-1 mt-4">
                        <label for="id_beneficiario" class="form-label text-muted small">ID:</label>
                        <input id="id_beneficiario" name="id_beneficiario" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $id; ?>" readonly>
                    </div>
                    <div class="col-10 col-sm-5 mt-4">
                        <label for="nombre" class="form-label text-muted small">NOMBRE :</label>
                        <input id="nombre" name="nombre" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $beneficiario['Nombre']; ?>">
                    </div>

                    <div class="col-10 col-sm-2 mt-4">
                        <label for="tipo" class="form-label text-muted small">TIPO :</label>
                        <select id="tipo" name="tipo"
                            class="form-control input-transparent border-0 border-bottom rounded-0 text-muted"
                            style="background-color: transparent;">
                            <option value="1" <?php echo ($beneficiario['Tipo'] == 1) ? 'selected' : ''; ?>>PHCA</option>
                            <option value="2" <?php echo ($beneficiario['Tipo'] == 2) ? 'selected' : ''; ?>>GASTOS
                                GENERALES</option>
                        </select>
                    </div>

                    <div class="col-10 col-sm-2 mt-4">
                        <label for="rfc" class="form-label text-muted small">RFC :</label>
                        <input id="rfc" name="rfc" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $beneficiario['Rfc']; ?>">
                    </div>

                    <div class="col-10 col-sm-4 mt-4">
                        <select id="subcuenta-select" name="subcuentas[]" multiple
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1">
                            <?php foreach ($subcuenta as $cuenta): ?>
                                <option value="<?php echo $cuenta['Id']; ?>" data-numero="<?php echo $cuenta['Numero']; ?>"
                                    <?php echo in_array($cuenta['Id'], $subcuentaIds) ? 'selected' : ''; ?>>
                                    <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>
                <div class="row">
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="usuarioAlta" class="form-label text-muted small">USUARIO ALTA :</label>
                        <input id="usuarioAlta" name="usuarioAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;"
                            value="<?php echo $beneficiario['NombreUsuarioAlta']; ?>" readonly>
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="fechaAlta" class="form-label text-muted small">FECHA ALTA :</label>
                        <input id="fechaAlta" name="fechaAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $beneficiario['FechaAlta']; ?>"
                            readonly>
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="status" class="form-label text-muted small">STATUS :</label>
                        <input id="status" name="status" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php
                            if ($beneficiario['Activo'] == 1) {
                                echo 'ACTIVO';
                            } else {
                                echo 'INACTIVO';
                            }
                            ?>" readnly>
                    </div>
                    <div class="row justify-content-end mt-5">
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" class="btn btn-outline-danger rounded-0"
                                onclick="window.location.href='../../vistas/catalogos/cat_Beneficiarios.php'">Salir</button>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" id="btn_editar" class="btn btn-secondary rounded-0">Modificar</button>
                        </div>
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="submit" class="btn btn-success rounded-0" id="btn_guardar"
                                style="display:none;">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

<script>
    $(document).ready(function () {
        // Inicializar Select2
        $('#subcuenta-select').select2({
            placeholder: 'Subcuenta (Amexport Logística)*',
            allowClear: true,
            width: '100%'
        });

        $('#tipo-select').on('change', function () {
            const tipo = $(this).val();
            let filtro = '';

            if (tipo === '1') {
                filtro = '123-';
            } else if (tipo === '2') {
                filtro = '601-';
            }

            // Mostrar u ocultar opciones según filtro
            $('#subcuenta-select option').each(function () {
                const numero = $(this).data('numero');
                if (numero && numero.startsWith(filtro)) {
                    $(this).show();
                } else {
                    $(this).hide().prop('selected', false); // También deselecciona si estaba elegido
                }
            });

            // Refrescar Select2 para que oculte/actualice el dropdown
            $('#subcuenta-select').val(null).trigger('change');

            
        });
    });
</script>

<script src="../../../js/actualizar/actualizar_Beneficiarios.js"></script>

</body>

</html>