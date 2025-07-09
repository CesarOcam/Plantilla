<?php
include_once(__DIR__ . '/../conexion.php');

$sql = "SELECT 
            idusuarios, 
            nombreUsuario,
            emailUsuario, 
            tipoUsuario
        FROM usuarios";

$stmt = $con->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th scope="col"></th>
            <th scope="col">Id</th>
            <th scope="col">Nombre</th>
            <th scope="col">E-mail</th>
            <th scope="col">Perfil</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($usuarios): ?>
            <?php foreach ($usuarios as $row): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = 'detalle_usuario.php?id=<?php echo $row['idusuarios']; ?>';}" style="cursor: pointer;">
                    <th scope="row">
                        <input class="form-check-input mt-1 chkUsuario" type="checkbox" value="<?php echo htmlspecialchars($row['idusuarios']); ?>" aria-label="Checkbox for following text input">
                    </th>
                    <td><?php echo htmlspecialchars($row['idusuarios']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombreUsuario']); ?></td>
                    <td><?php echo htmlspecialchars($row['emailUsuario']); ?></td>
                    <td><?php echo htmlspecialchars($row['tipoUsuario']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No se encontraron registros</td></tr>
        <?php endif; ?>
    </tbody>
</table>