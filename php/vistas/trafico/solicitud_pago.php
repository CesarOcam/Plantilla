<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');

//Obtener ADUANAS
$stmt = $con->prepare("SELECT Id, Numero 
                       FROM referencias 
                       WHERE Status = 1");
$stmt->execute();
$referencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Obtener REFERENCIAS
$stmt = $con->prepare("SELECT id2201aduanas, nombre_corto_aduana 
                       FROM 2201aduanas 
                       WHERE nombre_corto_aduana IS NOT NULL 
                       AND TRIM(nombre_corto_aduana) != '' ORDER BY nombre_corto_aduana");
$stmt->execute();
$aduana = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Obtener SUBCUENTAS
$stmt = $con->prepare("
    SELECT Id, Numero, Nombre 
    FROM cuentas
    WHERE Activo = 1
      AND EmpresaId = 2
      AND (
          SUBSTRING_INDEX(Numero, '-', 1) = '120' OR
          SUBSTRING_INDEX(Numero, '-', 1) = '123'
      )
    ORDER BY Nombre
");

$stmt->execute();
$subcuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Obtener BENEFICIARIOS
$stmt = $con->prepare("
    SELECT Id, Nombre 
    FROM beneficiarios
    ORDER BY Nombre
");
$stmt->execute();
$beneficiario = $stmt->fetchAll(PDO::FETCH_ASSOC);

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


    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/style2.css">
</head>

<?php
include_once __DIR__ . '/../../../config.php';

include($_SERVER['DOCUMENT_ROOT'] . $base_url . '/php/vistas/navbar.php');
?>

<div class="container-fluid">
    <div class="card mt-3 border shadow rounded-0">
        <form id="form_solicitud_pago" method="POST">
            <div class="card-header formulario_polizas">
                <h5>Registrar Solicitud</h5>
                <div class="row mb-5">
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <input id="empresa" name="empresa" type="text"
                            class="form-control rounded-0 border-0 border-bottom" style="background-color: transparent;"
                            placeholder="AMEXPORT LOGÍSTICA" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" readonly>
                    </div>
                    <div class="col-10 col-sm-3 d-flex align-items-center mt-4">
                        <input type="hidden" id="aduana-hidden" name="aduana" value="">
                        <select id="aduana-select" name="aduana"
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1">
                            <option value="" selected disabled>Aduana</option>
                            <?php foreach ($aduana as $aduana): ?>
                                <option value="<?php echo $aduana['id2201aduanas']; ?>">
                                    <?php echo $aduana['nombre_corto_aduana']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2 col-sm-3 d-flex align-items-center mt-4">
                        <select id="beneficiario-select" name="beneficiario"
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                            <option value="" selected disabled hidden>Beneficiario</option>
                            <?php foreach ($beneficiario as $beneficiario): ?>
                                <option value="<?php echo $beneficiario['Id']; ?>">
                                    <?php echo $beneficiario['Nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Plantilla oculta para subcuentas -->
                <select id="subcuenta_template" class="d-none">
                    <option value="">Seleccione</option>
                    <?php foreach ($subcuentas as $subcuenta): ?>
                        <option value="<?php echo $subcuenta['Id']; ?>">
                            <?php echo $subcuenta['Nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Tabla dinámica -->
                <div class="row mt-5">
                    <div class="col-12">
                        <table class="table-partidas" id="tabla-partidas">
                            <thead>
                                <tr class="text-muted">
                                    <th class="col-subcuenta">Subcuenta</th>
                                    <th class="col-referencia">Referencia</th>
                                    <th class="col-cargo">Importe</th>
                                    <th class="col-observaciones">Observaciones</th>
                                    <th class="col-accion">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filas dinámicas -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-end text-muted">Totales:</td>
                                    <td><input type="text" id="total-cargo" class="form-control text-end" readonly></td>
                                    <td><input type="text" id="total-abono" class="form-control text-end" readonly></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Botón para agregar partida -->
                    <div class="col-12 text-end mt-2">
                        <button type="button" class="btn btn-outline-primary" onclick="agregarFila()">+ Agregar
                            Partida</button>
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

    const referencias = <?php echo json_encode($referencia); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar Select2 SOLO UNA VEZ
        $('#aduana-select').select2({
            placeholder: 'Aduana*',
            allowClear: false,
            width: '100%'
        });

        $('#beneficiario-select').select2({
            placeholder: 'Beneficiario',
            allowClear: false,
            width: '100%'
        });
    });

    function agregarFila() {
        // Leer la letra de la aduana actual cada vez que se agrega una fila
        const aduanaSelect = document.getElementById('aduana-select');
        const selectedOption = aduanaSelect.options[aduanaSelect.selectedIndex]?.text.trim() || '';
        const letraAduanaSeleccionada = selectedOption.charAt(0).toUpperCase();

        if (!letraAduanaSeleccionada) {
            alert("Primero selecciona una aduana.");
            return;
        }

        // Filtrar referencias por letra de la aduana
        const referenciasFiltradas = referencias.filter(r => r.Numero.charAt(0).toUpperCase() === letraAduanaSeleccionada);

        const tbody = document.querySelector('#tabla-partidas tbody');
        const fila = document.createElement('tr');

        // Generar opciones de referencia
        let referenciaOptions = '<option value="">Seleccione</option>';
        referenciasFiltradas.forEach(ref => {
            referenciaOptions += `<option value="${ref.Id}">${ref.Numero}</option>`;
        });

        fila.innerHTML = `
        <td>
            <select name="Subcuenta[]" class="form-control select2" style="width:180px;" required>
                <option value="">Seleccione</option>
                <?php foreach ($subcuentas as $subcuenta): ?>
                    <option value="<?php echo $subcuenta['Id']; ?>">
                        <?php echo htmlspecialchars($subcuenta['Numero'] . ' - ' . $subcuenta['Nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="Referencia[]" class="form-control select2" style="width:180px;" required>
                ${referenciaOptions}
            </select>
        </td>
        <td>
            <input type="number" name="Cargo[]" step="0.01" class="form-control input-cargo" 
                oninput="calcularTotales()" placeholder="0.00" />
        </td>
        <td>
            <input type="text" name="Observaciones[]" class="form-control" placeholder="Observaciones (opcional)" />
        </td>
        <td class="text-center">
            <button type="button" class="btn-eliminar" onclick="eliminarFila(this)" title="Eliminar fila">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;

        tbody.appendChild(fila);

        if (tbody.rows.length > 0) {
            aduanaSelect.disabled = true;
            document.getElementById('aduana-hidden').value = aduanaSelect.value;
        } else {
            aduanaSelect.disabled = false;
            document.getElementById('aduana-hidden').value = '';
        }

        // Inicializar Select2 SOLO para los selects de la fila recién creada
        $(fila).find('select.select2').select2({
            width: '100%',
            placeholder: "Seleccione una opción",
            allowClear: false
        });

        calcularTotales();
    }

    function eliminarFila(boton) {
        const fila = boton.closest('tr');
        fila.remove();
        calcularTotales();

        const tbody = document.querySelector('#tabla-partidas tbody');
        if (tbody.rows.length === 0) {
            document.getElementById('aduana-select').disabled = false;
        }
    }

    function calcularTotales() {
        let totalCargo = 0;
        document.querySelectorAll('.input-cargo').forEach(input => {
            const valor = parseFloat(input.value) || 0;
            totalCargo += valor;
        });

        document.getElementById('total-cargo').value = '$' + totalCargo.toFixed(2);
    }

</script>
<script src="../../../js/guardar_Solicitudes.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>