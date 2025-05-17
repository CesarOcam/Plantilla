<?php
    include_once(__DIR__ . '/../conexion.php');

    // Consulta
    $stmt = $con->prepare("SELECT id2221_recintos, inmueble_recintos, aduana_recintos FROM 2221_recintos"); // Cambia a tu tabla/campos reales
    $stmt->execute();
    $recintos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <?php if ($recintos): ?>
                        <?php foreach ($recintos as $recintos): ?>
                    <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_recintos.php?id=<?php echo $recintos['id2221_recintos']; ?>';}" style="cursor: pointer;">
                        <th scope="row"> 
                            <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                        </th>
                        <td><?php echo $recintos['id2221_recintos']; ?></td>
                        <td><?php echo $recintos['inmueble_recintos']; ?></td>
                        <td><?php echo $recintos['aduana_recintos']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
