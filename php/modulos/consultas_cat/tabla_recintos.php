<?php
    include_once(__DIR__ . '/../conexion.php');

    // Consulta
    $stmt = $con->prepare("SELECT id2221_recintos, inmueble_recintos, aduana_recintos FROM 2221_recintos"); // Cambia a tu tabla/campos reales
    $stmt->execute();
    $navieras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table  class="table table-hover">
                    <thead class="small">
                        <tr>
                        <th scope="col"></th>
                        <th scope="col">Id</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Aduana</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                    <?php if ($navieras): ?>
                        <?php foreach ($navieras as $navieras): ?>
                        <tr>
                        <th scope="row"> 
                            <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                        </th>
                        <td><?php echo $navieras['id2221_recintos']; ?></td>
                        <td><?php echo $navieras['inmueble_recintos']; ?></td>
                        <td><?php echo $navieras['aduana_recintos']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
