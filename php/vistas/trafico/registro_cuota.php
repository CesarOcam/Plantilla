<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php'); // Ajusta el path según sea necesario

if (isset($_GET['referencia_id'])) {
    $referenciaId = (int) $_GET['referencia_id'];

    $stmt = $con->prepare("
        SELECT 
            a.id2201aduanas,
            a.nombre_corto_aduana, 
            e.id01clientes_exportadores,
            e.razonSocial_exportador
        FROM referencias r
        JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
        JOIN 01clientes_exportadores e ON r.ClienteExportadorId = e.id01clientes_exportadores
        WHERE r.Id = ?
    ");
    $stmt->execute([$referenciaId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result ?: [
        'nombre_corto_aduana' => '',
        'razonSocial_exportador' => ''
    ]);
    exit;
}


// Obtener todas las referencias
$stmt = $con->prepare("SELECT Id, Numero FROM referencias WHERE Numero IS NOT NULL AND Status IN (1, 2)");
$stmt->execute();
$referencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <!-- Fechas -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">
</head>

<?php
include_once __DIR__ . '/../../../config.php';

include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_Cuota" method="POST">
            <div class="card-header formulario_clientes">
                <div class="row align-items-center justify-content-between mb-3">
                    <!-- Título -->
                    <div class="col-auto">
                        <h5 class="mb-0">Registrar Cuota</h5>
                    </div>
                </div>
                <div class="row">
                    <!-- Columna izquierda: Subir archivos -->
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100 rounded-0">
                            <div class="card-body d-flex flex-column justify-content-center text-center p-4">
                                <h5 class="text-secondary mb-3">Subir Facturas</h5>
                                <div id="uploadBox"
                                    class="border p-4 bg-light d-flex flex-column align-items-center justify-content-center"
                                    style="border-style: dashed; min-height: 150px;">
                                    <div id="uploadPrompt" class="text-center">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-3"></i>
                                        <p class="text-muted small mb-2">Arrastra los archivos aquí o haz clic en el
                                            botón para subir</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2"
                                            onclick="document.getElementById('fileUpload').click();">
                                            Seleccionar Archivos
                                        </button>
                                        <div id="uploadAlert" class="rounded-0"
                                            style="margin-top: 1rem; display: none;"></div>
                                    </div>

                                    <input type="file" class="form-control-file d-none" id="fileUpload" multiple>
                                    <div id="fileRows" class="mt-4 w-100 text-start"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cold col-md-6">
                        <div class="row"></div>
                        <div class="card bg-body-light shadow-sm rounded-0">
                            <div class="card-body p-2">
                                <h5 class="ms-5">Datos de Factura</h5>
                                <!-- Aquí agregamos la fila -->
                                <div class="row p-5 pt-2">
                                    <!-- Primera columna -->
                                    <div class="col-md-6">
                                        <div class="d-flex flex-column mt-4">
                                            <label for="referencia"
                                                class="form-label text-muted small">Referencia:</label>
                                            <select id="referencia-select" name="referencia" class="form-control"
                                                required>
                                                <option value="" selected>Referencia</option>
                                                <?php foreach ($referencias as $referencia): ?>
                                                    <option value="<?= htmlspecialchars($referencia['Id']) ?>">
                                                        <?= htmlspecialchars($referencia['Numero']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="d-flex flex-column mt-4">
                                            <label for="aduana" class="form-label text-muted small">Aduana:</label>
                                            <input id="aduana" name="aduana" type="text"
                                                class="form-control input-transparent rounded-0"
                                                style="background-color: transparent;" placeholder="" readonly>
                                            <input type="hidden" id="aduanaHidden" name="aduanaHidden">
                                        </div>

                                        <div class="d-flex flex-column mt-4">
                                            <label for="exportador"
                                                class="form-label text-muted small">Exportador:</label>
                                            <input id="exportador" name="exportador" type="text"
                                                class="form-control input-transparent rounded-0"
                                                style="background-color: transparent;" placeholder="" readonly>
                                            <input type="hidden" id="exportadorHidden" name="exportadorHidden">
                                        </div>

                                        <div class="d-flex flex-column mt-4">
                                            <label for="observaciones"
                                                class="form-label text-muted small">Observaciones:</label>
                                            <input id="observaciones" name="observaciones" type="text"
                                                class="form-control input-transparent rounded-0"
                                                style="background-color: transparent;" placeholder="">
                                        </div>
                                    </div>

                                    <!-- Segunda columna -->
                                    <div class="col-md-6">
                                        <div class="d-flex flex-column mt-4">
                                            <label for="IVA" class="form-label text-muted small">IVA:</label>
                                            <input id="IVA" name="IVA" type="text"
                                                class="form-control input-transparent rounded-0"
                                                style="background-color: transparent;" placeholder="" readonly>
                                        </div>

                                        <div class="d-flex flex-column mt-4">
                                            <label for="subtotal" class="form-label text-muted small">Subtotal:</label>
                                            <input id="subtotal" name="subtotal" type="text"
                                                class="form-control input-transparent rounded-0"
                                                style="background-color: transparent;" placeholder="" readonly>
                                        </div>

                                        <div class="d-flex flex-column mt-4">
                                            <label for="monto" class="form-label text-muted small">Monto:</label>
                                            <input id="monto" name="monto" type="text"
                                                class="form-control input-transparent rounded-0"
                                                style="background-color: transparent;" placeholder="" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row justify-content-end mt-5">
                    <div class="col-auto d-flex align-items-center mt-3 mb-5">
                        <button type="submit" class="btn btn-secondary rounded-0" id="btn_guardar">Guardar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).on('select2:open', () => {
        setTimeout(() => {
            const input = document.querySelector('.select2-container--open .select2-search__field');
            if (input) input.focus();
        }, 100);
    });

</script>
<script src="../../../js/archivos/xml_Cuota.js"></script>
<script src="../../../js/guardar_Cuota.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>