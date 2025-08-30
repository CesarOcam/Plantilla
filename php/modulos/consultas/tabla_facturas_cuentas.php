<?php
include_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$registrosPorPagina = 20;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

$sql = "
SELECT 
    cu.Id,
    cu.Numero AS SubcuentaNumero,
    cu.Nombre AS SubcuentaNombre
FROM cuentas cu
WHERE cu.CuentaPadreId = 21
  AND cu.Numero LIKE '216-%';
";

$stmt = $con->prepare($sql);
$stmt->execute();
$acreedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($acreedores) {
    foreach ($acreedores as $acreedor) {
        $sql2 = "
        SELECT pp.Partida AS Id, pp.Cargo, pp.Observaciones AS FacturaObservaciones, 
            pp.NumeroFactura AS Factura, 
            p.Id AS PolizaId, 
            p.Numero AS PolizaNumero, 
            p.FechaAlta AS FechaHora, 
            r.Numero AS ReferenciaNumero, 
            b.Nombre AS BeneficiarioNombre, 
            cu.Numero AS SubcuentaNumero, 
            cu.Nombre AS SubcuentaNombre,  
            ( SELECT SubcuentaId FROM conta_partidaspolizas pp2 WHERE pp2.PolizaId = pp.PolizaId ORDER BY pp2.Partida DESC LIMIT 1 ) AS UltimaSubcuentaId
            FROM conta_partidaspolizas pp 
            LEFT JOIN conta_polizas p ON p.Id = pp.PolizaId 
            LEFT JOIN beneficiarios b ON b.Id = p.BeneficiarioId 
            LEFT JOIN cuentas cu ON cu.Id = pp.SubcuentaId 
            INNER JOIN conta_referencias r ON r.Id = pp.ReferenciaId 
            LEFT JOIN 2201aduanas a ON a.id2201aduanas = r.AduanaId 
            WHERE p.Id IN ( 
                SELECT PolizaId FROM conta_partidaspolizas WHERE (PolizaId, Partida) 
                IN ( SELECT PolizaId, MAX(Partida) FROM conta_partidaspolizas GROUP BY PolizaId ) 
                AND SubcuentaId = :subcuentaId ) 
                AND pp.Pagada = 0 
                ORDER BY p.Fecha DESC, pp.Partida ASC 
        ";
       $stmt2 = $con->prepare($sql2);
        $stmt2->execute([':subcuentaId' => $acreedor['Id']]);
        $resultados = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $totalCargo = 0;

        if ($resultados) {
            foreach ($resultados as $resultado) {
                $Cargo = (float) $resultado['Cargo'];
                $totalCargo += $Cargo;
            }

            // aquí construyes un registro para tu tabla
            $polizas[] = [
                'Id'              => $acreedor['Id'],
                'SubcuentaNumero' => $acreedor['SubcuentaNumero'],
                'SubcuentaNombre' => $acreedor['SubcuentaNombre'],
                'TotalRelacionado'=> $totalCargo,
                'TotalAbonado'    => 0, // o lo que quieras calcular después
            ];
        }
    }
}

foreach ($polizas as &$poliza) {
    $totalAbonado = floatval($poliza['TotalAbonado']);
    $totalRelacionado = floatval($poliza['TotalRelacionado']);
    $poliza['SaldoPendiente'] = $totalRelacionado - $totalAbonado;
}
unset($poliza); 

?>
<div id="tabla-pp-wrapper">
    <table id="tabla-pp-subcuentas" class="table table-hover tabla-pp-container">
        <thead class="small">
            <tr>
                <th scope="col">Num.Subcuenta</th>
                <th scope="col">Subcuenta</th>
                <th scope="col">Cargo Total</th>
            </tr>
        </thead>
        <tbody class="small">
            <?php if ($polizas): ?>
                <?php
                $ultimaFila = null;
                foreach ($polizas as $poliza):
                    if (($poliza['SaldoPendiente'] ?? 0) != 0) {
                        if (($poliza['SubcuentaNumero'] ?? '') === '216-003') {
                            $ultimaFila = $poliza;
                            continue;
                        }
                        ?>
                        <tr>
                            <td>
                                <a href="#" class="link-subcuenta" data-id="<?php echo htmlspecialchars($poliza['Id']); ?>"
                                    style="color:blue; text-decoration:none;">
                                    <?php echo htmlspecialchars($poliza['SubcuentaNumero']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($poliza['SubcuentaNombre'] ?? ''); ?></td>
                            <td><?php echo '$ ' . number_format($poliza['SaldoPendiente'] ?? 0, 2); ?></td>
                        </tr>
                        <?php
                    }
                endforeach;

                if ($ultimaFila): ?>
                    <tr>
                        <td>
                            <a href="#" class="link-subcuenta" data-id="<?php echo htmlspecialchars($ultimaFila['Id']); ?>"
                                style="color:blue; text-decoration:none;">
                                <?php echo htmlspecialchars($ultimaFila['SubcuentaNumero']); ?>
                            </a>

                        </td>
                        <td><?php echo htmlspecialchars($ultimaFila['SubcuentaNombre'] ?? ''); ?></td>
                        <td><?php echo '$ ' . number_format($ultimaFila['SaldoPendiente'] ?? 0, 2); ?></td>
                    </tr>
                <?php endif; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">No se encontraron registros</td>
                </tr>
            <?php endif; ?>
        </tbody>

    </table>


<script>
    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("link-subcuenta")) {
            e.preventDefault();

            const subcuentaId = e.target.getAttribute("data-id");
            console.log("Click en subcuenta:", subcuentaId);

            if (!subcuentaId) {
                console.error("No se encontró data-id en el enlace.");
                return;
            }

            const params = new URLSearchParams({ subcuenta: subcuentaId });
            console.log("Parámetros para AJAX:", params.toString());

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "../../modulos/consultas/tabla_facturas_pp.php?" + params.toString(), true);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    console.log("Respuesta recibida correctamente.");

                    // Reemplazas solo el contenido del wrapper
                    document.getElementById("tabla-pp-wrapper").innerHTML = xhr.responseText;

                    // Reaplicas funciones que dependen del DOM
                    setTimeout(() => {
                        // forzar subcuenta seleccionada para habilitar checkboxes
                        const subcuentaInput = document.getElementById('subcuentaInput');
                        if (subcuentaInput) {
                            subcuentaInput.value = subcuentaId; // el ID que pasaste al AJAX
                        }
                        actualizarEstadoCheckboxesYBoton();
                        actualizarTotalCargo();
                    }, 50);
                } else {
                    console.error("Error en la solicitud AJAX:", xhr.status, xhr.statusText);
                    console.error("Respuesta del servidor:", xhr.responseText);
                }
            };

            try {
                xhr.send();
            } catch (err) {
                console.error("Error enviando la solicitud AJAX:", err);
            }
        }
    });


</script>