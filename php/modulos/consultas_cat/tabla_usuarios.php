<?php
include_once(__DIR__ . '/../conexion.php');

$sql = "
    SELECT 
        u.login, 
        u.name,
        u.email, 
        u.idDepartamento,
        d.departamentos AS departamento,  -- nombre del departamento
        u.active
    FROM sec_users u
    LEFT JOIN departamentos d 
        ON u.idDepartamento = d.iddepartamentos
";

$stmt = $con->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table class="table table-hover">
    <thead class="small">
        <tr>
            <th></th>
            <th scope="col">Login</th>
            <th scope="col">Nombre</th>
            <th scope="col">E-mail</th>
            <th scope="col">Departamento</th>
            <th scope="col">Activo</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($usuarios): ?>
            <?php foreach ($usuarios as $row): ?>
                <tr onclick="if(event.target.type !== 'checkbox') {window.location.href = '#';}" style="cursor: pointer;">
                    <td></td>
                    <td><?php echo htmlspecialchars($row['login']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['departamento']); ?></td>
                    <td>
                        <?php
                        echo ($row['active'] === 'Y') ? 'Activo' : 'Inactivo';
                        ?>
                    </td>

                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No se encontraron registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>