<?php
    include_once(__DIR__ . '/../conexion.php');

    // Consulta
    $stmt = $con->prepare("SELECT Id, Nombre FROM navieras"); // Cambia a tu tabla/campos reales
    $stmt->execute();
    $navieras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table  class="table table-hover">
                    <thead class="small">
                        <tr>
                        <th scope="col"></th>
                        <th scope="col">Id</th>
                        <th scope="col">Nombre</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                    <?php if ($navieras): ?>
                        <?php foreach ($navieras as $navieras): ?>
                    <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_navieras.php?id=<?php echo $navieras['Id']; ?>';}" style="cursor: pointer;">
                        <th scope="row"> 
                            <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                        </th>
                        <td><?php echo $navieras['Id']; ?></td>
                        <td><?php echo $navieras['Nombre']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
