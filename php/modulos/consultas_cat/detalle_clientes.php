<?php
include_once(__DIR__ . '/../conexion.php'); // Ajusta el path según sea necesario

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmt = $con->prepare("
    SELECT 
        ce.razonSocial_exportador, ce.curp_exportador, ce.rfc_exportador, ce.tipoClienteExportador, ce.tipo_cliente,
        ce.nombreCorto_exportador, ce.calle_exportador, ce.noExt_exportador, ce.noInt_exportador, ce.codigoPostal_exportador,
        ce.colonia_exportador, ce.localidad_exportador, ce.municipio_exportador,
        ce.idcat11_estado, est.estado, ce.id2204clave_pais, pais.pais_clave,
        ce.contacto_cliente, ce.telefono_cliente, ce.emails_trafico, ce.pagaCon_cliente,
        ce.status_exportador, ce.fechaAlta_exportador, ce.usuarioAlta_exportador,
        logi.razonSocial_exportador AS razonSocial_logistico,
        u.nombre AS nombre_usuario_alta
    FROM 01clientes_exportadores ce
    LEFT JOIN cat11_estados est ON ce.idcat11_estado = est.idcat11_estado
    LEFT JOIN 2204claves_paises pais ON ce.id2204clave_pais = pais.id2204clave_pais
    LEFT JOIN 01clientes_exportadores logi ON ce.logistico_asociado = logi.id01clientes_exportadores
    LEFT JOIN usuarios u ON ce.usuarioAlta_exportador = u.id
    WHERE ce.id01clientes_exportadores = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Clientes</title>
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
                    <h5> Información de Cliente</h5>
                    <div class="row">
                            <!--<div class="col-2 col-sm-1 d-flex align-items-center mt-4">
                                <input name="id" type="text" class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;" placeholder="ID" aria-label="Filtrar por fecha" aria-describedby="basic-addon1">
                            </div>-->
                            <div class="col-10 col-sm-2 mt-4">
                                <label for="nombre" class="form-label text-muted small">CLIENTE:</label>
                                <input id="razonSocial" name="razonSocial" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['razonSocial_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-2 mt-4">
                                <label for="curp" class="form-label text-muted small">CURP:</label>
                                <input id="curp" name="curp" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['curp_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-2 mt-4">
                                <label for="rfc" class="form-label text-muted small">RFC:</label>
                                <input id="rfc" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['rfc_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-2 mt-4">
                                <label for="tipo_cliente" class="form-label text-muted small">PERSONA:</label>
                                <input id="tipo_cliente" name="tipo_cliente" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" 
                                    value="<?php 
                                        if ($cliente['tipoClienteExportador'] == 1) {
                                            echo 'FÍSICA';
                                        } elseif ($cliente['tipoClienteExportador'] == 2) {
                                            echo 'MORAL';
                                        } else {
                                            echo '';
                                        }
                                    ?>" 
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-2 mt-4">
                                <label for="tipo_cliente" class="form-label text-muted small">TIPO:</label>
                                <input id="tipo_cliente" name="tipo_cliente" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" 
                                    value="<?php 
                                        if ($cliente['tipo_cliente'] == 1) {
                                            echo 'EXPORTADOR';
                                        } elseif ($cliente['tipo_cliente'] == 2) {
                                            echo 'LOGISTICO';
                                        } elseif ($cliente['tipo_cliente'] == 3) {
                                            echo 'EXPORTADOR Y LOGISTICO';
                                        } else {
                                            echo '';
                                        }
                                    ?>" 
                                    readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10 col-sm-6 mt-4">
                                <label for="nombreCorto_exportador" class="form-label text-muted small">Nombre Conocido:</label>
                                <input id="nombreCorto_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['nombreCorto_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-2 mt-4">
                                <label for="contacto_cliente" class="form-label text-muted small">Contacto:</label>
                                <input id="contacto_cliente" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['contacto_cliente']; ?>"
                                    readonly>
                            </div>
                          <div class="col-10 col-sm-2 mt-4">
                                <label for="telefono_cliente" class="form-label text-muted small">TELÉFONO:</label>
                                <input id="telefono_cliente" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['telefono_cliente']; ?>"
                                    readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10 col-sm-3 mt-4">
                                <label for="calle_exportador" class="form-label text-muted small">CALLE:</label>
                                <input id="calle_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['calle_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-1 mt-4">
                                <label for="noExt_exportador" class="form-label text-muted small">NO. EXTERIOR:</label>
                                <input id="noExt_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['noExt_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-1 mt-4">
                                <label for="noInt_exportador" class="form-label text-muted small">NO. INTERIOR:</label>
                                <input id="noExt_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['noInt_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-1 mt-4">
                                <label for="codigoPostal_exportador" class="form-label text-muted small">CP:</label>
                                <input id="codigoPostal_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['codigoPostal_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-3 mt-4">
                                <label for="colonia_exportador" class="form-label text-muted small">COLONIA:</label>
                                <input id="colonia_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['colonia_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-3 mt-4">
                                <label for="localidad_exportador" class="form-label text-muted small">LOCALIDAD:</label>
                                <input id="localidad_exportador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['localidad_exportador']; ?>"
                                    readonly>
                            </div>
                        </div>
 
                        <div class="row">
                            <div class="col-10 col-sm-3 mt-4">
                                <label for="municipio_exportador" class="form-label text-muted small">MUNICIPIO:</label>
                                <input id="municipio_exportadorrtador" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['municipio_exportador']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="pais_clave" class="form-label text-muted small">PAÍS:</label>
                                <input id="pais_clave" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['pais_clave']; ?>"
                                    readonly>
                            </div>
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="estado" class="form-label text-muted small">ESTADO:</label>
                                <input id="estado" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" value="<?php echo $cliente['estado']; ?>"
                                    readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="pagaCon_cliente" class="form-label text-muted small">PAGA CON:</label>
                                <input id="pagaCon_cliente" name="rfc" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" 
                                    value="<?php 
                                        if ($cliente['pagaCon_cliente'] == 1) {
                                            echo 'CUENTA CLIENTE';
                                        } elseif ($cliente['pagaCon_cliente'] == 2) {
                                            echo 'CUENTA AMEXPORT';
                                        } else {
                                            echo '';
                                        }
                                    ?>" 
                                    readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="razonSocial_logistico" class="form-label text-muted small">LOGÍSTICO ASOCIADO:</label>
                                <input id="razonSocial_logistico" name="razonSocial_logistico" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;"
                                    value="<?php echo $cliente['razonSocial_logistico']; ?>" readonly>
                            </div>
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="emails_trafico" class="form-label text-muted small">EMAIL LOGÍSTICO:</label>
                                <input id="emails_trafico" name="emails_trafico" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;"
                                    value="<?php echo $cliente['emails_trafico']; ?>" readonly>
                            </div>
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="status_exportador" class="form-label text-muted small">ESTADO:</label>
                                <input id="status_exportador" name="status_exportador" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;" 
                                    value="<?php 
                                        if ($cliente['status_exportador'] == 1) {
                                            echo 'ACTIVO';
                                        } elseif ($cliente['status_exportador'] == 0) {
                                            echo 'INACTIVO';
                                        } else {
                                            echo '';
                                        }
                                    ?>" 
                                    readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="nombre_usuario_alta" class="form-label text-muted small">USUARIO ALTA:</label>
                                <input id="nombre_usuario_alta" name="nombre_usuario_alta" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;"
                                    value="<?php echo $cliente['nombre_usuario_alta']; ?>" readonly>
                            </div>
                            <div class="col-10 col-sm-4 mt-4">
                                <label for="fechaAlta_exportador" class="form-label text-muted small">FECHA ALTA:</label>
                                <input id="fechaAlta_exportador" name="fechaAlta_exportador" type="text"
                                    class="form-control input-transparent border-0 border-bottom rounded-0"
                                    style="background-color: transparent;"
                                    value="<?php echo $cliente['fechaAlta_exportador']; ?>" readonly>
                            </div>
                        </div>
                    <div class="row mt-3"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

</body>
</html>