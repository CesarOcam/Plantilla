<?php
include_once('../../modulos/conexion.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmt = $con->prepare("
    SELECT 
    b.Id, b.Numero, b.Nombre, b.TipoSaldo, b.EmpresaId, b.Activo, b.FechaAlta, b.UsuarioAlta,
    u.Nombre AS NombreUsuarioAlta
    FROM cuentas b
    LEFT JOIN usuarios u ON b.UsuarioAlta = u.Id
    WHERE b.Id = :id
");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
include($_SERVER['DOCUMENT_ROOT'] . '/portal_web/proyecto_2/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Cuentas" method="POST">
            <div class="card-header formulario_clientes">
                <h5>Información de Cuenta</h5>
                <div class="row">
                    
                    <div class="col-10 col-sm-1 mt-4">
                        <label for="id_cuenta" class="form-label text-muted small">ID :</label>
                        <input id="id_cuenta" name="id_cuenta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $cuenta['Id']; ?>" readonly>
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="numero" class="form-label text-muted small">NUMERO :</label>
                        <input id="numero" name="numero" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $cuenta['Numero']; ?>">
                    </div>
                    <div class="col-10 col-sm-4 mt-4">
                        <label for="nombre" class="form-label text-muted small">NOMBRE :</label>
                        <input id="nombre" name="nombre" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $cuenta['Nombre']; ?>">
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="empresa" class="form-label text-muted small">EMPRESA :</label>
                        <input id="empresa" name="empresa" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php
                            if ($cuenta['EmpresaId'] == 1) {
                                echo 'AMEXPORT';
                            } else {
                                echo 'AMEXPORT LOGÍSTICA';
                            }
                            ?>" disabled>
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="tipo" class="form-label text-muted small">TIPO :</label>
                        <select id="tipo" name="tipo"
                            class="form-control input-transparent border-0 border-bottom rounded-0 text-muted"
                            style="background-color: transparent;">
                            <option value="1" <?php echo ($cuenta['TipoSaldo'] == 1) ? 'selected' : ''; ?>>ACREEDOR
                            </option>
                            <option value="2" <?php echo ($cuenta['TipoSaldo'] != 1) ? 'selected' : ''; ?>>DEUDOR
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-10 col-sm-4 mt-4">
                        <label for="usuarioAlta" class="form-label text-muted small">USUARIO ALTA :</label>
                        <input id="usuarioAlta" name="usuarioAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $cuenta['NombreUsuarioAlta']; ?>"
                            readonly>
                    </div>
                    <div class="col-10 col-sm-4 mt-4">
                        <label for="fechaAlta" class="form-label text-muted small">FECHA ALTA :</label>
                        <input id="fechaAlta" name="fechaAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $cuenta['FechaAlta']; ?>" readonly>
                    </div>
                </div>
                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="button" class="btn btn-outline-danger rounded-0"
                            onclick="window.location.href='../../vistas/catalogos/cat_Cuentas.php'">Salir</button>
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
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>
<script src="../../../js/actualizar/actualizar_Cuentas.js"></script>
</body>

</html>