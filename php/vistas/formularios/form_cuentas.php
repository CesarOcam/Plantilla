<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /portal_web/Contabilidad/login.php');  // Ruta desde la raíz del servidor web
    exit;
}
include_once('../../modulos/conexion.php');
$stmt = $con->prepare("SELECT Id, Numero, Nombre FROM cuentas WHERE CuentaPadreId IS NOT NULL"); // Cambia a tu tabla/campos reales
$stmt->execute();
$subcuenta = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Cuentas</title>
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
        <form id="form_Cuentas" method="POST">
            <div class="card-header formulario_clientes">
                <h5>+ Agregar Cuenta</h5>
                <div class="row">
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="numero" type="text" class="form-control rounded-0 border-0 border-bottom"
                            maxlength="7" style="background-color: transparent;" placeholder="Numero"
                            aria-label="Filtrar por fecha" aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-4 d-flex align-items-center mt-4">
                        <input name="nombre" type="text" class="form-control rounded-0 border-0 border-bottom"
                            style="background-color: transparent;" placeholder="Nombre*" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                    </div>
                    <div class="col-10 col-sm-2 d-flex align-items-center mt-4">
                        <select id="tipo_saldo-select" name="tipo_saldo"
                            class="form-control rounded-0 border-0 border-bottom text-muted"
                            style="background-color: transparent;" aria-label="Filtrar por fecha"
                            aria-describedby="basic-addon1" required>
                            <option value="" selected disabled>Tipo</option>
                            <option value="1">Acreedor</option>
                            <option value="2">Deudor</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-12">
                        <table class="table-subcuentas" id="tabla-subcuentas">
                            <thead>
                                <tr class="text-muted">
                                    <th class="col-numero_Subcuenta">Subcuenta</th>
                                    <th class="col-nombre_Subcuenta">Nombre</th>
                                    <th class="col-saldo_Subcuenta">Saldo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filas dinámicas -->
                            </tbody>
                            <tfoot>
                                <tr>

                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Botón para agregar partida -->
                    <div class="col-12 text-end mt-2">
                        <button type="button" class="btn btn-outline-primary" onclick="agregarFila()">+ Agregar
                            Subcuenta</button>
                    </div>
                </div>
                <div class="row">
                    <div class="row justify-content-end mt-5">
                        <div class="col-auto d-flex align-items-center mt-3 mb-5">
                            <button type="button" class="btn btn-outline-danger rounded-0"
                                onclick="window.location.href='../../vistas/catalogos/cat_Cuentas.php'">Salir</button>
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
    let contadorFilas = 0;
    function agregarFila() {
        const tbody = document.querySelector('#tabla-subcuentas tbody');
        const fila = document.createElement('tr');

        fila.innerHTML = `
        <td>
            <input type="text" name="numero_subcuenta[${contadorFilas}]" class="form-control" placeholder="Agregar Subcuenta" />
        </td>
        <td>
            <input type="text" name="nombre_subcuenta[${contadorFilas}]" class="form-control" placeholder="Agregar Nombre" />
        </td>
        <td>
            <input type="number" name="saldo_subcuenta[${contadorFilas}]" step="0.01" class="form-control text-center" placeholder="$ 0.00" />
        </td>
        <td class="text-center">
            <button type="button" class="btn-eliminar" onclick="eliminarFila(this)" title="Eliminar fila">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
        `;

        tbody.appendChild(fila);
        contadorFilas++;
    }

    function eliminarFila(boton) {
        const fila = boton.closest('tr');
        fila.remove();
        calcularTotales(); // actualizar totales al eliminar
    }

</script>
<script src="../../../js/guardar_Cuenta.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
    crossorigin="anonymous"></script>

</body>

</html>