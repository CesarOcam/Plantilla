<?php
    include_once(__DIR__ . '/../conexion.php');

    // Consulta
    $stmt = $con->prepare("SELECT id2201aduanas, aduana_aduana, seccion_aduana, nombre_corto_aduana, denominacion_aduana, prefix_aduana, tipoAduana, status_aduana FROM 2201aduanas ORDER BY status_aduana DESC"); // Cambia a tu tabla/campos reales
    $stmt->execute();
    $aduanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table  class="table table-hover">
                    <thead class="small">
                        <tr>
                        <th scope="col"></th>
                        <th scope="col">Id</th>
                        <th scope="col">Aduana Sección</th>
                        <th scope="col">Nombre Corto</th>
                        <th scope="col">Denominación</th>
                        <th scope="col">Prefijo</th>
                        <th scope="col">Tipo Aduana</th>
                        <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                    <?php if ($aduanas): ?>
                        <?php foreach ($aduanas as $aduanas): ?>
                        <tr>
                        <th scope="row"> 
                            <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                        </th>
                        <td><?php echo $aduanas['id2201aduanas']; ?></td>
                        <td><?php echo $aduanas['aduana_aduana'] . '-' . $aduanas['seccion_aduana']; ?></td>
                        <td><?php echo $aduanas['nombre_corto_aduana']; ?></td>
                        <td><?php echo $aduanas['denominacion_aduana']; ?></td>
                        <td><?php echo $aduanas['prefix_aduana']; ?></td>
                        <td>
                            <?php 
                                echo ($aduanas['tipoAduana'] == 'M') ? 'Marítimo' : 
                                    (($aduanas['tipoAduana'] == 'T') ? 'Terrestre' : 
                                    (($aduanas['tipoAduana'] == 'A') ? 'Aéreo' : ''));
                            ?>
                        </td>
                        <td>
                            <?php 
                                echo ($aduanas['status_aduana'] == 1) ? 'ACTIVO' : 
                                    (($aduanas['status_aduana'] == 0) ? 'INACTIVO' : 'Otro');
                            ?>
                        </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
