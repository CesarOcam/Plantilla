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
        a.nombre_corto_aduana, 
        a.aduana_aduana, 
        a.seccion_aduana, 
        a.denominacion_aduana, 
        a.prefix_aduana,
        a.tipoAduana,

        sc1.Id AS SubcuentaClientesLogId,
        CONCAT(sc1.Numero, ' - ', sc1.Nombre) AS SubcuentaClientesLogNombre,

        sc2.Id AS SubcuentaClientesExpId,
        CONCAT(sc2.Numero, ' - ', sc2.Nombre) AS SubcuentaClientesExpNombre,

        sc3.Id AS SubcuentaCuotasAbonoLogId,
        CONCAT(sc3.Numero, ' - ', sc3.Nombre) AS SubcuentaCuotasAbonoLogNombre,

        sc4.Id AS SubcuentaCuotasAbonoExpId,
        CONCAT(sc4.Numero, ' - ', sc4.Nombre) AS SubcuentaCuotasAbonoExpNombre,

        sc5.Id AS SubcuentaCuotasCargoLogId,
        CONCAT(sc5.Numero, ' - ', sc5.Nombre) AS SubcuentaCuotasCargoLogNombre,

        sc6.Id AS SubcuentaCuotasCargoExpId,
        CONCAT(sc6.Numero, ' - ', sc6.Nombre) AS SubcuentaCuotasCargoExpNombre,


        a.fechaCreate_aduana, 
        a.usuarioAlta_aduana, 
        a.status_aduana,
        u.Nombre AS NombreUsuarioAlta

    FROM 2201aduanas a
    LEFT JOIN usuarios u ON a.usuarioAlta_aduana = u.Id

    LEFT JOIN cuentas sc1 ON a.SubcuentaClientesLogId = sc1.Id
    LEFT JOIN cuentas sc2 ON a.SubcuentaClientesExpId = sc2.Id
    LEFT JOIN cuentas sc3 ON a.SubcuentaCuotasAbonoLogId = sc3.Id
    LEFT JOIN cuentas sc4 ON a.SubcuentaCuotasAbonoExpId = sc4.Id
    LEFT JOIN cuentas sc5 ON a.SubcuentaCuotasCargoLogId = sc5.Id
    LEFT JOIN cuentas sc6 ON a.SubcuentaCuotasCargoExpId = sc6.Id

    WHERE a.id2201aduanas = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$aduana = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas"); // Cambia a tu tabla/campos reales
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
include($_SERVER['DOCUMENT_ROOT'] . '/portal_web/Contabilidad/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Aduanas" method="POST">
            <div class="card-header formulario_aduanas">
                <h5>Información de Aduana</h5>
                <div class="row">
                    <div class="col-10 col-sm-1 mt-4">
                        <label for="id_aduana" class="form-label text-muted small">ID:</label>
                        <input id="id_aduana" name="id_aduana" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $id; ?>" readonly>
                    </div>
                    <div class="col-10 col-sm-4 mt-4">
                        <label for="nombre" class="form-label text-muted small">NOMBRE CORTO :</label>
                        <input id="nombre" name="nombre" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;"
                            value="<?php echo $aduana['nombre_corto_aduana']; ?>">
                    </div>

                    <div class="col-10 col-sm-2 mt-4">
                        <label for="clave" class="form-label text-muted small">CLAVE :</label>
                        <input id="clave" name="clave" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $aduana['aduana_aduana']; ?>">
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="seccion" class="form-label text-muted small">CLAVE SECCION :</label>
                        <input id="seccion" name="seccion" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $aduana['seccion_aduana']; ?>">
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="denominacion" class="form-label text-muted small">DENOMINACION :</label>
                        <input id="denominacion" name="denominacion" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;"
                            value="<?php echo $aduana['denominacion_aduana']; ?>">
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="prefijo" class="form-label text-muted small">PREFIJO :</label>
                        <input id="prefijo" name="prefijo" type="text"
                            class="form-control input-transparent border-0 border-bottom rounded-0"
                            style="background-color: transparent;" value="<?php echo $aduana['prefix_aduana']; ?>">
                    </div>
                    <div class="col-10 col-sm-2 mt-4">
                        <label for="tipo" class="form-label text-muted small">TIPO ADUANA :</label>
                        <select id="tipo" name="tipo"
                            class="form-control input-transparent border-0 border-bottom rounded-0 text-muted"
                            style="background-color: transparent;">
                            <option value="M" <?php echo ($aduana['tipoAduana'] == 'M') ? 'selected' : ''; ?>>MARÍTIMA
                            </option>
                            <option value="A" <?php echo ($aduana['tipoAduana'] == 'A') ? 'selected' : ''; ?>>AÉREA
                            </option>
                            <option value="T" <?php echo ($aduana['tipoAduana'] != 'M' && $aduana['tipoAduana'] != 'A') ? 'selected' : ''; ?>>TERRESTRE</option>
                        </select>
                    </div>
                </div>
                
                <hr class="mt-5" style="border-top: 1px solid #000;">

                <div class="row">
                <!-- Columna 1: Logístico -->
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-12 col-sm-12 mt-4">
                            <label for="subcuenta_log" class="form-label text-muted small">SUBCUENTA CLIENTE LOGÍSTICO:</label>
                            <select id="subcuenta_log" name="subcuenta_log"
                                class="form-control select2 rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;">
                                <option value="" disabled hidden>Selecciona una subcuenta</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        <?php echo ($cuenta['Id'] == $aduana['SubcuentaClientesLogId']) ? 'selected' : ''; ?>>
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-12 mt-4">
                            <label for="subcuenta_ab_log" class="form-label text-muted small">SUBCUENTA CUOTAS ABONO LOGÍSTICO:</label>
                            <select id="subcuenta_ab_log" name="subcuenta_ab_log"
                                class="form-control select2 rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;">
                                <option value="" disabled hidden>Selecciona una subcuenta</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        <?php echo ($cuenta['Id'] == $aduana['SubcuentaCuotasAbonoLogId']) ? 'selected' : ''; ?>>
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-12 mt-4">
                            <label for="sub_cargo_log" class="form-label text-muted small">SUBCUENTA CUOTAS CARGO LOGÍSTICO:</label>
                            <select id="sub_cargo_log" name="sub_cargo_log"
                                class="form-control select2 rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;">
                                <option value="" disabled hidden>Selecciona una subcuenta</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        <?php echo ($cuenta['Id'] == $aduana['SubcuentaCuotasCargoLogId']) ? 'selected' : ''; ?>>
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: Exportador -->
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-12 col-sm-12 mt-4">
                            <label for="subcuenta_exp" class="form-label text-muted small">SUBCUENTA CLIENTE EXPORTADOR:</label>
                            <select id="subcuenta_exp-select" name="subcuenta_exp"
                                class="form-control select2 rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;">
                                <option value="" disabled hidden>Selecciona una subcuenta</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>" 
                                        data-numero="<?php echo $cuenta['Numero']; ?>"
                                        <?php echo ($cuenta['Id'] == $aduana['SubcuentaClientesExpId']) ? 'selected' : ''; ?>>
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-12 mt-4">
                            <label for="subcuenta_ab_exp" class="form-label text-muted small">SUBCUENTA CUOTAS ABONO EXPORTADOR:</label>
                            <select id="subcuenta_ab_exp" name="subcuenta_ab_exp"
                                class="form-control select2 rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;">
                                <option value="" disabled hidden>Selecciona una subcuenta</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        <?php echo ($cuenta['Id'] == $aduana['SubcuentaClientesExpId']) ? 'selected' : ''; ?>>
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-12 mt-4">
                            <label for="sub_cargo_exp" class="form-label text-muted small">SUBCUENTA CUOTAS CARGO EXPORTADOR:</label>
                            <select id="sub_cargo_exp" name="sub_cargo_exp"
                                class="form-control select2 rounded-0 border-0 border-bottom text-muted"
                                style="background-color: transparent; width: 100%;">
                                <option value="" disabled hidden>Selecciona una subcuenta</option>
                                <?php foreach ($subcuenta as $cuenta): ?>
                                    <option value="<?php echo $cuenta['Id']; ?>"
                                        <?php echo ($cuenta['Id'] == $aduana['SubcuentaCuotasCargoExpId']) ? 'selected' : ''; ?>>
                                        <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="button" class="btn btn-outline-danger rounded-0"
                            onclick="window.location.href='../../vistas/catalogos/cat_Aduanas.php'">Salir</button>
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

<script src="../../../js/actualizar/actualizar_Aduanas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous">
</script>
<script>
  $(document).ready(function() {
    console.log('Inicializando Select2'); // para verificar que corre
    $('.select2').select2({
      width: 'resolve',
      placeholder: "Selecciona una subcuenta",
      allowClear: true
    });
  });
</script>



</body>

</html>