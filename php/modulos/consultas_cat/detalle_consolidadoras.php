<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmt = $con->prepare("
    SELECT 
    b.id_consolidadora, b.denominacion_consolidadora, b.status_consolidadora, b.fechaCreate_consolidadora, b.userCreate_consolidadora,
    u.Nombre AS NombreUsuarioAlta
    FROM consolidadoras b
    LEFT JOIN usuarios u ON b.userCreate_consolidadora = u.Id
    WHERE b.id_consolidadora = :id
");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$consolidadora = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Consolidadora</title>
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
        <form id="form_Consolidadoras" method="POST">
            <div class="card-header formulario_buques">
                <h5>Información de Consolidadora</h5>
                <div class="row">
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="id_consolidadora" class="form-label text-muted small">ID:</label>
                        <input id="id_consolidadora" name="id_consolidadora" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $id; ?>" readonly>
                    </div>
                    <div class="col-10 col-sm-6 mt-4">
                        <label for="nombre" class="form-label text-muted small">TIPO :</label>
                        <input id="nombre" name="nombre" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $consolidadora['denominacion_consolidadora']; ?>"
                            >
                    </div>
                </div>
                <div class="row">
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="usuarioAlta" class="form-label text-muted small">USUARIO ALTA :</label>
                        <input id="usuarioAlta" name="usuarioAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $consolidadora['NombreUsuarioAlta']; ?>"
                            readonly>
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="fechaAlta" class="form-label text-muted small">FECHA ALTA :</label>
                        <input id="fechaAlta" name="fechaAlta" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $consolidadora['fechaCreate_consolidadora']; ?>" readonly>
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="status" class="form-label text-muted small">STATUS :</label>
                        <input id="status" name="status" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php
                             if($consolidadora['status_consolidadora'] == 1) {
                                echo 'ACTIVO';
                             }else{
                                echo 'INACTIVO';
                             }
                             ?>" readonly>
                    </div>
                    <div class="row justify-content-end mt-5">
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-outline-danger rounded-0" onclick="window.location.href='../../vistas/catalogos/cat_Clientes.php'">Salir</button>
                            </div>
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" id="btn_editar" class="btn btn-secondary rounded-0">Modificar</button>
                            </div>
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="submit" class="btn btn-success rounded-0" id="btn_guardar" style="display:none;">Guardar</button>
                            </div>
                        </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous">
</script>
    
<script src="../../../js/actualizar/actualizar_Consolidadoras.js"></script>

</body>

</html>