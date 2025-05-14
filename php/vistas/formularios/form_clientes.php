<?php
include_once('../../modulos/conexion.php');

// Consulta para obtener los países
$stmt = $con->prepare("SELECT id2204clave_pais, CONCAT(clave_SAAI_M3, ' - ', pais_clave) AS nombre_pais 
                       FROM 2204claves_paises 
                       ORDER BY clave_SAAI_M3, pais_clave");
$stmt->execute();
$paises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obtener los estados
$stmt = $con->prepare("SELECT idcat11_estado, CONCAT(cveEdos, ' - ', estado) AS nombre_estado 
                       FROM cat11_estados 
                       ORDER BY cveEdos, estado");
$stmt->execute();
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obtener los logísticos
$stmt = $con->prepare("SELECT id01clientes_exportadores, razonSocial_exportador 
                        FROM `01clientes_exportadores` 
                        WHERE tipo_cliente = 1
                        ORDER BY razonSocial_exportador");
$stmt->execute();
$logisticos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
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
            <form id="form_Clientes" method="POST">
                <div class="card-header formulario_clientes">
                    <h5> + Agregar Cliente</h5>
                    <div class="row">
                            <!--<div class="col-2 col-sm-1 d-flex align-items-center mt-4">
                                <input name="id" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="ID" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>-->
                            <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                                <input name="nombre" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Nombre/Razón Social*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <input name="curp" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="CURP" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <input name="rfc" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="RFC*" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="persona-select" name="persona" class="form-control rounded-0 border-0 border-bottom text-muted"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha"
                                    aria-describedby="basic-addon1">
                                    <option value="" selected disabled>Persona*</option>
                                    <option value="1">FÍSICA</option>
                                    <option value="2">MORAL</option>
                                </select>
                            </div>
                            <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                                <select id="tipo-select" name="tipo" class="form-control rounded-0 border-0 border-bottom text-muted"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha"
                                    aria-describedby="basic-addon1">
                                    <option value="" selected disabled>Tipo*</option>
                                    <option value="1">EXPORTADOR</option>
                                    <option value="2">LOGÍSTICO</option>
                                    <option value="3">EXPORTADOR Y LOGÍSTICO</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-2 col-sm-6 d-flex align-items-center mt-4">
                                <input name="nombre_corto" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Nombre Conocido" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-2 col-sm-3 d-flex align-items-center mt-4">
                                <input name="contacto" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Contacto" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-2 col-sm-3 d-flex align-items-center mt-4">
                                <input name="tel" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Teléfono" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-2 col-sm-3 d-flex align-items-center mt-4">
                                <input name="calle" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Calle" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-1 d-flex align-items-center  mt-4">
                                <input name="num_exterior" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Número Exterior" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-1 d-flex align-items-center mt-4">
                                <input name="num_interior" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Número Interior" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-1 d-flex align-items-center mt-4">
                                <input name="cp" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Código Postal" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-2 col-sm-3 d-flex align-items-center mt-4">
                                <input name="colonia" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Colonia" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-10 col-sm-3 d-flex align-items-center  mt-4">
                                <input name="localidad" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Localidad" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                        </div>
                        <div class="row">

                        </div>

                        <div class="row">
                            <div class="col-2 col-sm-4 d-flex align-items-center mt-4">
                                <input name="municipio" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="Municipio" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-2 col-sm-4 d-flex align-items-center mt-4">
                                <select id="pais-select" name="pais" class="form-control rounded-0 border-0 border-bottom text-muted"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha"
                                    aria-describedby="basic-addon1">
                                    <option value="" selected disabled>Pais</option>
                                    <?php foreach ($paises as $pais): ?>
                                        <option value="<?php echo $pais['id2204clave_pais']; ?>">
                                            <?php echo $pais['nombre_pais']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2 col-sm-4 d-flex align-items-center mt-4">
                                <select id="estado-select" name="estado" class="form-control rounded-0 border-0 border-bottom text-muted"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha"
                                    aria-describedby="basic-addon1">
                                    <option value="" selected disabled>Estado</option>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?php echo $estado['idcat11_estado']; ?>">
                                            <?php echo $estado['nombre_estado']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                       <div class="row">
                            <div class="col-2 col-sm-6 d-flex align-items-center mt-4 mb-3 ms-3">
                                <div class="row w-100 mt-2 mb-3">
                                    <label class="col-form-label col-sm-12 text-muted" style="background-color: transparent;">
                                        Paga con:
                                    </label>
                                    <div class="col-sm-12 d-flex align-items-center mt-2" style="background-color: transparent;">
                                        <div class="form-check me-4" style="background-color: transparent;">
                                            <input class="form-check-input" type="radio" name="quien_paga" id="cuenta_cliente" value="1" required>
                                            <label class="form-check-label" for="cuenta_cliente" style="background-color: transparent;">
                                                Cuenta Cliente
                                            </label>
                                        </div>
                                        <div class="form-check" style="background-color: transparent;">
                                            <input class="form-check-input" type="radio" name="quien_paga" id="cuenta_amexport" value="2">
                                            <label class="form-check-label" for="cuenta_amexport" style="background-color: transparent;">
                                                Cuenta Amexport
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-2 col-sm-4 d-flex align-items-center mt-4 mb-3">
                                <select id="logistico_asociado-select" name="logistico_asociado" class="form-control rounded-0 border-0 border-bottom text-muted"
                                    style="background-color: transparent;" aria-label="Filtrar por fecha"
                                    aria-describedby="basic-addon1">
                                    <option value="" selected disabled>Logístico</option>
                                    <?php foreach ($logisticos as $logisticos): ?>
                                        <option value="<?php echo $logisticos['id01clientes_exportadores']; ?>">
                                            <?php echo $logisticos['razonSocial_exportador']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>              
                            <div class="col-2 col-sm-4 d-flex align-items-center mt-4 mb-3">
                                <input name="email_trafico" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="E-Mails de Logístico" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>
                            <div class="col-2 col-sm-4 d-flex align-items-center mt-4 mb-3">
                                <label class="form-label me-3 text-muted mb-0" style="background-color: transparent;">Estado:</label>
                                <div class="form-check form-check-inline" style="background-color: transparent;">
                                    <input class="form-check-input" type="radio" name="status_exportador" id="status_exportador" value="1" required>
                                    <label class="form-check-label" for="estado_activo" style="background-color: transparent;">Activo</label>
                                </div>
                                <div class="form-check form-check-inline" style="background-color: transparent;">
                                    <input class="form-check-input" type="radio" name="status_exportador" id="status_exportador" value="0">
                                    <label class="form-check-label" for="estado_inactivo" style="background-color: transparent;">Inactivo</label>
                                </div>
                            </div>
                        </div>


                        <div class="row justify-content-end mt-5">
                            <div class="col-auto d-flex align-items-center mt-3 mb-5">
                                <button type="button" class="btn btn-outline-danger rounded-0">Salir</button>
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
    $(document).ready(function() {
        // Inicializar Select2
        $('#pais-select').select2({
            placeholder: 'País',
            allowClear: true,
            width: '100%'
        });
         $('#estado-select').select2({
            placeholder: 'Estado',
            allowClear: true,
            width: '100%'
        });
        $('#logistico_asociado-select').select2({
            placeholder: 'Logístico',
            allowClear: true,
            width: '100%'
        });
    });
</script>
<script src="../../../js/guardar_Cliente.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

</body>
</html>