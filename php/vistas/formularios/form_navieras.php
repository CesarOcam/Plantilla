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
include($_SERVER['DOCUMENT_ROOT'] . '/portal_web/Contabilidad/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Navieras" method="POST">
            <div class="card-header formulario_clientes">
                <h5>+ Agregar Naviera</h5>
                <div class="row">
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="nombre_transportista" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="Nombre*" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="rfc_transportista" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="RFC*" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="curp_transportista" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="CURP*" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="domicilio_fiscal_transportista" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="Domicilio Fiscal*" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input id="fechaAcceso_transportista" name="fechaAcceso_transportista" type="datetime-local"
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>

                </div>
                <div class="row">
                    <div class="row justify-content-end mt-5">
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" class="btn btn-outline-danger rounded-0"
                                onclick="window.location.href='../../vistas/catalogos/cat_Navieras.php'">Salir</button>
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
    e.preventDefault();
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const localDatetime = now.toISOString().slice(0,16); // formato para datetime-local
        document.getElementById('fechaAcceso_transportista').value = localDatetime;
    });
</script>

<script src="../../../js/guardar_Naviera.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>
