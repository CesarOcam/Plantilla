<?php
    include_once(__DIR__ . '/../conexion.php');

    // Consulta
    $stmt = $con->prepare("SELECT id_consolidadora, denominacion_consolidadora FROM consolidadoras"); // Cambia a tu tabla/campos reales
    $stmt->execute();
    $consolidadora = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <?php if ($consolidadora): ?>
                        <?php foreach ($consolidadora as $consolidadora): ?>
                        <tr>
                        <th scope="row"> 
                            <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                        </th>
                        <td><?php echo $consolidadora['id_consolidadora']; ?></td>
                        <td><?php echo $consolidadora['denominacion_consolidadora']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">No se encontraron registros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
