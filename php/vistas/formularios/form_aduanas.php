<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');
$stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas"); // Cambia a tu tabla/campos reales
$stmt->execute();
$subcuenta = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    include_once __DIR__ . '/../../../config.php';

    include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
    ?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Aduanas" method="POST">
            <div class="card-header formulario_clientes">
                <h5> + Agregar Aduana</h5>
                <div class="row">
                    <!--<div class="col-2 col-sm-1 d-flex align-items-center mt-4">
                                <input name="id" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="ID" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>-->
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="nombre_corto_aduana" type="text" maxlength="15"
                            class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;"
                            placeholder="Nombre Corto*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <input name="aduana_aduana" type="text" maxlength="3"
                            class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;"
                            placeholder="Clave Aduana*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <input name="seccion_aduana" type="text" maxlength="1"
                            class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;"
                            placeholder="Clave Seccion*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <input name="denominacion_aduana" type="text"
                            class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;"
                            placeholder="Denominación*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <input name="prefix_aduana" type="text" maxlength="10"
                            class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;"
                            placeholder="Prefijo*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <select id="tipoAduana-select" name="tipoAduana"
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                            <option value="" selected disabled>Tipo de Aduana*</option>
                            <option value="M">MARÍTIMO</option>
                            <option value="A">AÉREO</option>
                            <option value="T">TERRESTRE</option>
                        </select>
                    </div>
                </div>

                <hr class="mt-5" style="border-top: 1px solid #000;">

                <div class="row">
                    <div class="col-md-6">
                        <div class="col-2 col-sm-12 d-flex align-items-center mt-4">
                            <select id="SubcuentaClientesLogId-select" name="SubcuentaClientesLogId"
                                class="form-control rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                                aria-describedby="basic-addon1" required>
                                <option value="" disabled selected hidden>Subcuenta Logístico</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        data-numero="<?php echo $cuenta['Numero']; ?>">
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-2 col-sm-12 d-flex align-items-center mt-4">
                            <select id="SubcuentaCuotasAbonoLogId-select" name="SubcuentaCuotasAbonoLogId"
                                class="form-control rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                                aria-describedby="basic-addon1" required>
                                <option value="" disabled selected hidden>Subcuenta Cuotas Abono Logístico</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        data-numero="<?php echo $cuenta['Numero']; ?>">
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-2 col-sm-12 d-flex align-items-center mt-4">
                            <select id="SubcuentaCuotasCargoLogId-select" name="SubcuentaCuotasCargoLogId"
                                class="form-control rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                                aria-describedby="basic-addon1" required>
                                <option value="" disabled selected hidden>Subcuenta Cuotas Cargo Logístico</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        data-numero="<?php echo $cuenta['Numero']; ?>">
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="col-2 col-sm-12 d-flex align-items-center mt-4">
                            <select id="SubcuentaClientesExpId-select" name="SubcuentaClientesExpId"
                                class="form-control rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                                aria-describedby="basic-addon1" required>
                                <option value="" disabled selected hidden>Subcuenta Exportador</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        data-numero="<?php echo $cuenta['Numero']; ?>">
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-2 col-sm-12 d-flex align-items-center mt-4">
                            <select id="SubcuentaCuotasAbonoExpId-select" name="SubcuentaCuotasAbonoExpId"
                                class="form-control rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                                aria-describedby="basic-addon1" required>
                                <option value="" disabled selected hidden>Subcuenta Cuotas Abono Exportador</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        data-numero="<?php echo $cuenta['Numero']; ?>">
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-2 col-sm-12 d-flex align-items-center mt-4">
                            <select id="SubcuentaCuotasCargoExpId-select" name="SubcuentaCuotasCargoExpId"
                                class="form-control rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                                aria-describedby="basic-addon1" required>
                                <option value="" disabled selected hidden>Subcuenta Cuotas Cargo Logístico</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        data-numero="<?php echo $cuenta['Numero']; ?>">
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="button" class="btn btn-outline-danger rounded-0"
                            onclick="window.location.href='../../vistas/catalogos/cat_Aduanas.php'">Salir</button>
                    </div>
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="submit" class="btn btn-secondary rounded-0" id="btn_guardar">Guardar</button>
                    </div>
                </div>

            </div>
    </div>
    </form>
</div>
</div>


<script>
    $(document).ready(function () {
        // Inicializar Select2
        $('#SubcuentaClientesLogId-select').select2({
            placeholder: 'Subcuenta Logístico',
            allowClear: true,
            width: '100%'
        });
        $('#SubcuentaClientesExpId-select').select2({
            placeholder: 'Subcuenta Exportador',
            allowClear: true,
            width: '100%'
        });
        $('#SubcuentaCuotasAbonoLogId-select').select2({
            placeholder: 'Subcuenta Cuotas Abono Logístico',
            allowClear: true,
            width: '100%'
        });
        $('#SubcuentaCuotasAbonoExpId-select').select2({
            placeholder: 'Subcuenta Cuotas Abono Exportador',
            allowClear: true,
            width: '100%'
        });
        $('#SubcuentaCuotasCargoLogId-select').select2({
            placeholder: 'Subcuenta Cuotas Cargo Logístico',
            allowClear: true,
            width: '100%'
        });
        $('#SubcuentaCuotasCargoExpId-select').select2({
            placeholder: 'Subcuenta Cuotas Cargo Exportador',
            allowClear: true,
            width: '100%'
        });
    });
</script>
<script src="../../../js/guardar_Aduana.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>