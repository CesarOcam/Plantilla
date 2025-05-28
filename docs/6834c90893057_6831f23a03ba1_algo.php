<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Póliza de Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            border: 1px solid #ccc;
            padding: 20px;
        }
        .row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .field {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }
        input, select {
            padding: 6px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[readonly] {
            background-color: #f5f5f5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
        }
        .btn {
            padding: 6px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            height: 38px;
            margin-top: 22px;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .subaccounts {
            margin-top: 30px;
        }
    </style>
</head>
<body>


<div class="container">
    <form method="post" action="">

        <!-- Primera fila -->
        <div class="row">
            <div class="field">
                <label>Beneficiario</label>
                <div style="display: flex; gap: 5px;">
                    <select id="beneficiario" name="beneficiario" style="width: 100%;">
                                                    <option value="TESORERIA DE LA FEDERACION">TESORERIA DE LA FEDERACION</option>
                                                    <option value="OTRO BENEFICIARIO">OTRO BENEFICIARIO</option>
                                            </select>
                    <button type="button" class="btn">↗</button>
                </div>
            </div>

            <div class="field">
                <label>Fecha Póliza</label>
                <input type="date" name="fecha_poliza" value="2025-05-10">
            </div>

            <div class="field">
                <label>Póliza No.</label>
                <input type="text" name="poliza_no" value="C0056105" readonly>
            </div>
        </div>

        <!-- Segunda fila -->
        <div class="row">
            <div class="field">
                <label>Solicitud No.</label>
                <input type="text" name="solicitud_no" value="55407" readonly>
            </div>

            <div class="field">
                <label>Fecha</label>
                <input type="text" name="fecha" value="10/05/2025" readonly>
            </div>

            <div class="field">
                <label>Aduana</label>
                <input type="text" name="aduana" value="" readonly>
            </div>

            <div class="field">
                <label>Empresa</label>
                <input type="text" name="empresa" value="AMEXPORT LOGISTICA" readonly>
            </div>
        </div>

        <div class="subaccounts">
            <table>
                <thead>
                    <tr>
                        <th>Subcuenta</th>
                        <th>Descripción</th>
                        <th>Referencia</th>
                        <th>Exportador</th>
                        <th>Cargo</th>
                        <th>Abono</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody id="subcuentas-lista">
                                            <tr>
                            <td>123-001</td>
                            <td>PEDIMENTO - CONTRIBUCIONES</td>
                            <td>M50056</td>
                            <td>ADALBE DOMINGO HERNANDEZ MARTINEZ</td>
                            <td>$1,500.00</td>
                            <td>$0.00</td>
                            <td>Editar observaciones...</td>
                        </tr>
                                            <tr>
                            <td>123-001</td>
                            <td>PEDIMENTO - CONTRIBUCIONES</td>
                            <td>M50167</td>
                            <td>CONGELADORA COMFRUT SA DE CV</td>
                            <td>$2,500.00</td>
                            <td>$0.00</td>
                            <td>Editar observaciones...</td>
                        </tr>
                                            <tr>
                            <td>123-001</td>
                            <td>PEDIMENTO - CONTRIBUCIONES</td>
                            <td>M50203</td>
                            <td>CONGELADORA COMFRUT SA DE CV</td>
                            <td>$100.00</td>
                            <td>$0.00</td>
                            <td>Editar observaciones...</td>
                        </tr>
                                    </tbody>
            </table>

            <button type="button" class="btn" onclick="agregarSubcuenta()">Agregar subcuenta</button>
        </div>

        <div class="footer">
            <p class="total">TOTAL CARGO: $4,100.00</p>
            <p class="total">TOTAL ABONO: $4,100.00</p>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#beneficiario').select2();
});

function agregarSubcuenta() {
    if (document.getElementById('subcuentas-lista').querySelectorAll('.nueva-subcuenta').length === 0) {
        let nuevaFila = `
            <tr class="nueva-subcuenta">
                <td><input type="text" name="subcuenta_nueva" required></td>
                <td><input type="text" name="descripcion_nueva" required></td>
                <td><input type="text" name="referencia_nueva" required></td>
                <td><input type="text" name="exportador_nueva" required></td>
                <td><input type="number" step="0.01" name="cargo_nueva" required></td>
                <td><input type="number" step="0.01" name="abono_nueva" required></td>
                <td><input type="text" name="observaciones_nueva"></td>
            </tr>
        `;
        $('#subcuentas-lista').append(nuevaFila);
    } else {
        alert('Ya has agregado una nueva subcuenta.');
    }
}
</script>

</body>
</html>
