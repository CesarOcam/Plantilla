<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');
    exit;
}
include_once('../../modulos/conexion.php');
$stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas WHERE CuentaPadreId IS NOT NULL");
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
        <form id="form_Beneficiarios" method="POST">
            <div class="card-header formulario_clientes">
                <h5>+ Agregar Beneficiario</h5>
                <div class="row">
                    <div class="col-10 col-sm-5 d-flex align-items-center mt-4">
                        <input name="nombre" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="Nombre*" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <select id="tipo-select" name="tipo"
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1">
                            <option value="" selected disabled hidden>Tipo*</option>
                            <option value="1">PHCA</option>
                            <option value="2">Gastos Generales</option>
                        </select>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <input name="rfc" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="RFC" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1">
                    </div>
                </div>
                <div class="row">
                    <div class="col-2 col-sm-6 d-flex align-items-center mt-4">
                        <select id="subcuenta-select" name="subcuentas[]" multiple
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent; width: 100%;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1">
                            <?php foreach ($subcuenta as $cuenta): ?>
                                <option value="<?php echo $cuenta['Id']; ?>" data-numero="<?php echo $cuenta['Numero']; ?>">
                                    <?php echo $cuenta['Numero'] . ' - ' . $cuenta['Nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row justify-content-end mt-5">
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" class="btn btn-outline-danger rounded-0"
                                onclick="window.location.href='../../vistas/catalogos/cat_Beneficiarios.php'">Salir</button>
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
        // Guardar todas las opciones originales
        const allOptions = $('#subcuenta-select option').toArray();

        $('#tipo-select').on('change', function () {
            const tipo = $(this).val();
            let filtro = '';

            if (tipo === '1') {
                filtro = '123-';
            } else if (tipo === '2') {
                filtro = '601-';
            }

            const filteredOptions = allOptions.filter(opt => {
                const numero = $(opt).data('numero');
                return numero && numero.startsWith(filtro);
            });

            $('#subcuenta-select').empty();

            filteredOptions.forEach(opt => $('#subcuenta-select').append(opt));

            $('#subcuenta-select').val(null).trigger('change');
        });

        $('#subcuenta-select').select2({
            placeholder: 'Subcuenta (Amexport Logística)*',
            allowClear: true,
            width: '100%'
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                const input = document.querySelector('.select2-container--open .select2-search__field');
                if (input) input.focus();
            }, 100);
        });
    });

</script>

<script src="../../../js/guardar_Beneficiario.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>