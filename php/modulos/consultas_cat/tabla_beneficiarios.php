<?php
    include_once(__DIR__ . '/../conexion.php');

    // Consulta
    $stmt = $con->prepare("SELECT Id, Nombre, Tipo, Rfc FROM beneficiarios"); // Cambia a tu tabla/campos reales
    $stmt->execute();
    $beneficiario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table  class="table table-hover">
                    <thead class="small">
                        <tr>
                        <th scope="col"></th>
                        <th scope="col">Id</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">RFC</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if ($beneficiario): ?>
                            <?php foreach ($beneficiario as $beneficiario): ?>
                        <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '../../modulos/consultas_cat/detalle_beneficiarios.php?id=<?php echo $beneficiario['Id']; ?>';}" style="cursor: pointer;">
                            <th scope="row"> 
                                <input class="form-check-input mt-1" type="checkbox" value="" aria-label="Checkbox for following text input">
                            </th>
                            <td><?php echo $beneficiario['Id']; ?></td>
                            <td><?php echo $beneficiario['Nombre']; ?></td>
                            <td>
                                <?php 
                                    echo ($beneficiario['Tipo'] == 1) ? 'PHCA' : 
                                        (($beneficiario['Tipo'] == 2) ? 'Gastos Generales' : 'Otro');
                                ?>
                            </td>
                            <td><?php echo $beneficiario['Rfc']; ?></td>
                        </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No se encontraron registros</td></tr>
                            <?php endif; ?>
                    </tbody>
                </table>
