<?php
include_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$registrosPorPagina = 20;
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

// Consulta principal: partir de cuentas, LEFT JOIN partidaspolizas filtrando partidas no pagadas
$sql = "
SELECT
    cu.Id,
    cu.Numero AS SubcuentaNumero,
    cu.Nombre AS SubcuentaNombre,
    
    -- Total abonado a esta cuenta 216-xxx (aún no pagado)
    COALESCE((
        SELECT SUM(pp1.Abono)
        FROM conta_partidaspolizas pp1
        WHERE pp1.SubcuentaId = cu.Id AND pp1.Pagada = 1 AND Activo = 1
    ), 0) AS TotalAbonado,

    -- Total cargado relacionado con las mismas pólizas o referencias que los abonos a esta cuenta
    COALESCE((
        SELECT SUM(pp2.Cargo)
        FROM conta_partidaspolizas pp2
        WHERE pp2.Pagada = 0
          AND pp2.SubcuentaId != cu.Id
          AND (
              pp2.PolizaId IN (
                  SELECT PolizaId
                  FROM conta_partidaspolizas
                  WHERE SubcuentaId = cu.Id AND Pagada = 0 AND Activo = 1
              )
              OR
              pp2.ReferenciaId IN (
                  SELECT ReferenciaId
                  FROM conta_partidaspolizas
                  WHERE SubcuentaId = cu.Id AND Pagada
              )
          )
    ), 0) AS TotalRelacionado

FROM cuentas cu
WHERE cu.CuentaPadreId = 21
  AND cu.Numero LIKE '216-%'
ORDER BY (TotalRelacionado - TotalAbonado) DESC, cu.Numero ASC
LIMIT :inicio, :registrosPorPagina
";

$stmt = $con->prepare($sql);
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registrosPorPagina', $registrosPorPagina, PDO::PARAM_INT);
$stmt->execute();
$polizas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($polizas as &$poliza) {
    $totalAbonado = floatval($poliza['TotalAbonado']);
    $totalRelacionado = floatval($poliza['TotalRelacionado']);
    $poliza['SaldoPendiente'] = $totalRelacionado - $totalAbonado;
}
unset($poliza); // buena práctica por si acaso


// Consulta para contar total de subcuentas hijas para paginación
$sqlCount = "
SELECT COUNT(*)
FROM cuentas cu
WHERE cu.CuentaPadreId = 21
  AND cu.Numero LIKE '216-%' 
";

$stmtCount = $con->prepare($sqlCount);
$stmtCount->execute();
$totalRegistros = $stmtCount->fetchColumn();

$totalPaginas = ceil($totalRegistros / $registrosPorPagina);
$inicioBloque = floor(($paginaActual - 1) / 10) * 10 + 1;
$finBloque = min($inicioBloque + 9, $totalPaginas);
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

<!-- Paginación 
<nav aria-label="Page navigation example" class="d-flex justify-content-center" id="paginacion-subcuentas">
    <ul class="pagination">
        <li class="page-item <?php echo ($paginaActual == 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php for ($i = $inicioBloque; $i <= $finBloque; $i++): ?>
            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($paginaActual == $totalPaginas) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>-->
</div>
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