<?php
include_once(__DIR__ . '/../conexion.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
// Número de registros por página
$registrosPorPagina = 20;

// Determinar el número de la página actual
$paginaActual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina; // Índice de inicio para la consulta

try {
    $stmt = $con->prepare("
        SELECT 
            p.Numero AS NumeroPoliza,
            b.Nombre AS NombreBeneficiario,
            CONCAT(c.Numero, ' - ', c.Nombre) AS Cuenta,
            pp.Partida AS IdPartida,
            pp.PolizaId,
            pp.Cargo AS Cargo,
            pp.Abono AS Abono,
            pp.Observaciones,
            pp.NumeroFactura
        FROM conta_partidaspolizas pp
        LEFT JOIN conta_polizas p ON pp.PolizaId = p.Id
        LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
        LEFT JOIN cuentas c ON pp.SubcuentaId = c.Id
        WHERE pp.ReferenciaId = :id
        AND c.Numero IN (123, 114, 214)
        AND pp.Activo = 1
        AND pp.EnKardex != 1
        AND pp.PagoCuenta !=1
        LIMIT :inicio, :limite
    ");

    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $registrosPorPagina, PDO::PARAM_INT);
    $stmt->execute();

    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);



    //-------------------------------------------------------------------------------------
    $stmt2 = $con->prepare("
        SELECT 
            p.Numero AS NumeroPoliza,
            b.Nombre AS NombreBeneficiario,
            CONCAT(c.Numero, ' - ', c.Nombre) AS Cuenta,
            pp.PolizaId,
            pp.Partida AS IdPartida,
            pp.Cargo AS Cargo,
            pp.Abono AS Abono,
            pp.Observaciones,
            pp.NumeroFactura
        FROM conta_partidaspolizas pp
        LEFT JOIN conta_polizas p ON pp.PolizaId = p.Id
        LEFT JOIN beneficiarios b ON p.BeneficiarioId = b.Id
        LEFT JOIN cuentas c ON pp.SubcuentaId = c.Id
        WHERE pp.ReferenciaId = :id
        AND (
            c.Numero NOT IN (114, 214, 123)
            OR (c.Numero = 123 AND pp.PagoCuenta = 1)
        )
        AND pp.EnKardex != 1
        AND pp.Activo = 1
        LIMIT :inicio, :limite
    ");


    $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt2->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt2->bindValue(':limite', $registrosPorPagina, PDO::PARAM_INT);
    $stmt2->execute();

    $datos2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
}


?>
<link rel="stylesheet" href="../../../css/style.css">
<link rel="stylesheet" href="../../../css/style2.css">

<div class="card card-tabla mb-4" id="tabla-movimientos-contenedor">
    <div class="card-body">
        <div class="row">
            <!-- Tabla estática -->
            <div class="col-lg-6 mb-3">
                <h6 class="text-muted fw-bold">Cuenta de Gastos</h6>
                <table class="table table-hover tabla-mov1 table-bordered shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Póliza</th>
                            <th>Beneficiario</th>
                            <th>Cuenta</th>
                            <th>Cargo</th>
                            <th>Abono</th>
                            <th>Observaciones/Fact</th>
                            <th>Archivo</th> <!-- NUEVO -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($datos): ?>
                            <?php foreach ($datos as $fila): ?>
                                <?php
                                // Verificar si ya hay archivo(s) para esta partida
                                $partidaId = (int) $fila['IdPartida'];
                                $sqlCheck = "SELECT COUNT(*) as total FROM conta_referencias_archivos WHERE Partida_id = $partidaId";
                                $resCheck = $con->query($sqlCheck)->fetch(PDO::FETCH_ASSOC);
                                $yaTieneArchivo = $resCheck['total'] > 0;
                                ?>
                                <tr class="small" data-partida-id="<?= $partidaId ?>" data-origen= 1 >
                                    <td>
                                        <a href="detalle_poliza.php?id=<?= $fila['PolizaId'] ?>">
                                            <?= $fila['NumeroPoliza'] ?>
                                        </a>
                                    </td>
                                    <td><?= $fila['NombreBeneficiario'] ?></td>
                                    <td><?= $fila['Cuenta'] ?></td>
                                    <td>$<?= number_format($fila['Cargo'], 2) ?></td>
                                    <td>$<?= number_format($fila['Abono'], 2) ?></td>
                                    <!-- OBSERVACIONES -->
                                    <td>
                                        <button type="button" class="btn btn-link p-0 text-start obs-edit"
                                            data-bs-toggle="modal" data-bs-target="#modalObservaciones"
                                            data-partida-id="<?= $partidaId ?>"
                                            data-observaciones="<?= htmlspecialchars($fila['Observaciones'] ?? '', ENT_QUOTES) ?>"
                                            title="Editar observaciones">

                                            <span class="observaciones-text">
                                                <?= !empty($fila['Observaciones']) ? htmlspecialchars($fila['Observaciones']) : '' ?>
                                            </span>
                                            <?php if (empty($fila['Observaciones'])): ?>
                                                <i class="bi bi-pencil-square ms-1 text-secondary" style="font-size: 1.2rem;"></i>
                                            <?php endif; ?>

                                        </button>
                                    </td>
                                    <!-- ARCHIVO -->
                                    <td class="text-center">
                                        <?php
                                        if ($yaTieneArchivo) {
                                            // Obtener el primer archivo para esta partida
                                            $sqlArchivo = "SELECT Nombre FROM conta_referencias_archivos WHERE Partida_id = $partidaId LIMIT 1";
                                            $resArchivo = $con->query($sqlArchivo)->fetch(PDO::FETCH_ASSOC);
                                            $nombreArchivo = htmlspecialchars($resArchivo['Nombre'] ?? 'Archivo');
                                            ?>
                                            <i class="bi bi-check-circle-fill text-success fs-5 me-1" style="cursor: pointer;"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Archivo subido: <?= $nombreArchivo ?>">
                                            </i>

                                            <?php
                                        } else {
                                            ?>
                                            <button type="button" class="btn btn-outline-success btn-sm upload-file"
                                                data-bs-toggle="modal" data-bs-target="#modalUploadArchivo"
                                                data-poliza-id="<?= (int) $fila['PolizaId'] ?>" data-partida-id="<?= $partidaId ?>"
                                                title="Subir archivo">
                                                <i class="bi bi-cloud-upload"></i>
                                            </button>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No se encontraron registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tabla dinámica -->
            <div class="col-lg-6 mb-3">
                <h6 class="text-muted fw-bold">Cuentas Operativo</h6>
                <table class="table table-hover tabla-mov2 table-bordered shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Póliza</th>
                            <th>Beneficiario</th>
                            <th>Cuenta</th>
                            <th>Cargo</th>
                            <th>Abono</th>
                            <th>Observaciones/Fact</th>
                            <th>Archivo</th> <!-- NUEVO -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($datos2): ?>
                            <?php foreach ($datos2 as $fila2): ?>
                                <?php
                                $partidaId = (int) $fila2['IdPartida'];
                                // Verificar si ya hay archivo(s) para esta partida
                                $sqlCheck = "SELECT COUNT(*) as total FROM conta_referencias_archivos WHERE Partida_id = $partidaId";
                                $resCheck = $con->query($sqlCheck)->fetch(PDO::FETCH_ASSOC);
                                $yaTieneArchivo = $resCheck['total'] > 0;
                                ?>
                                <tr class="small" data-partida-id="<?= $partidaId ?>" data-origen=2>
                                    <td>
                                        <a href="detalle_poliza.php?id=<?= $fila2['PolizaId'] ?>">
                                            <?= $fila2['NumeroPoliza'] ?>
                                        </a>
                                    </td>
                                    <td><?= $fila2['NombreBeneficiario'] ?></td>
                                    <td><?= $fila2['Cuenta'] ?></td>
                                    <td>$<?= number_format($fila2['Cargo'], 2) ?></td>
                                    <td>$<?= number_format($fila2['Abono'], 2) ?></td>

                                    <!-- OBSERVACIONES -->
                                    <td>
                                        <button type="button" class="btn btn-link p-0 text-start obs-edit"
                                            data-bs-toggle="modal" data-bs-target="#modalObservaciones"
                                            data-partida-id="<?= $partidaId ?>"
                                            data-observaciones="<?= htmlspecialchars($fila2['Observaciones'] ?? '', ENT_QUOTES) ?>"
                                            title="Editar observaciones">

                                            <span class="observaciones-text">
                                                <?= !empty($fila2['Observaciones']) ? htmlspecialchars($fila2['Observaciones']) : '' ?>
                                            </span>
                                            <?php if (empty($fila2['Observaciones'])): ?>
                                                <i class="bi bi-pencil-square ms-1 text-secondary" style="font-size: 1.2rem;"></i>
                                            <?php endif; ?>

                                        </button>
                                    </td>

                                    <!-- ARCHIVO -->
                                    <td class="text-center">
                                        <?php
                                        if ($yaTieneArchivo) {
                                            // Obtener el primer archivo para esta partida
                                            $sqlArchivo = "SELECT Nombre FROM conta_referencias_archivos WHERE Partida_id = $partidaId LIMIT 1";
                                            $resArchivo = $con->query($sqlArchivo)->fetch(PDO::FETCH_ASSOC);
                                            $nombreArchivo = htmlspecialchars($resArchivo['Nombre'] ?? 'Archivo');
                                            ?>
                                            <i class="bi bi-check-circle-fill text-success fs-5 me-1" style="cursor: pointer;"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Archivo subido: <?= $nombreArchivo ?>">
                                            </i>
                                            <?php
                                        } else {
                                            ?>
                                            <button type="button" class="btn btn-outline-success btn-sm upload-file"
                                                data-bs-toggle="modal" data-bs-target="#modalUploadArchivo"
                                                data-poliza-id="<?= (int) $fila2['PolizaId'] ?>" data-partida-id="<?= $partidaId ?>"
                                                title="Subir archivo">
                                                <i class="bi bi-cloud-upload"></i>
                                            </button>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No se encontraron registros</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Editar Observaciones -->
<div class="modal fade" id="modalObservaciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><!-- centrado -->
        <div class="modal-content">
            <form id="formObservaciones">
                <div class="modal-header">
                    <h5 class="modal-title">Editar observaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="obsPartidaId" name="obsPartidaId">
                    <div class="mb-3">
                        <label for="obsTexto" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="obsTexto" name="Observaciones" rows="4"
                            maxlength="1000"></textarea>
                        <div class="form-text">Máx. 1000 caracteres.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnGuardarObs" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Subir Archivo -->
<div class="modal fade" id="modalUploadArchivo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formUploadArchivo" enctype="multipart/form-data">

                <input type="hidden" name="PartidaId" id="uploadPartidaId">
                <input type="hidden" name="ReferenciaId" id="uploadReferenciaId" value="<?= $id ?>">
                <input type="hidden" name="Origen" id="uploadOrigen">

                <div class="modal-header">
                    <h5 class="modal-title">Subir Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="PolizaId" id="uploadPolizaId">

                    <!-- Drag & Drop -->
                    <div id="dropArea" class="border p-4 text-center mb-3" style="cursor:pointer;">
                        <i class="bi bi-cloud-upload fs-1"></i> <!-- fs-1 = font-size grande -->
                        <div id="dropText">Arrastra tu archivo aquí</div>
                    </div>


                    <!-- Input tradicional debajo -->
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Selecciona archivo</label>
                        <input class="form-control" type="file" id="archivo" name="archivo[]" multiple required>
                        <div class="form-text">Tipos sugeridos: PDF y XML.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnSubirArchivo" class="btn btn-success" disabled>Subir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const referenciaId = <?= $id ?>;
    document.addEventListener("DOMContentLoaded", function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

</script>
<script src="../../../js/movimientos_factura/movimientos_Observa.js"></script>
<script src="../../../js/movimientos_factura/movimientos_Factura.js"></script>