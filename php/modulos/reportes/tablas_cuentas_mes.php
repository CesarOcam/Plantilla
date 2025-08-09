<?php
include('../conexion.php');

$data = json_decode(file_get_contents("php://input"), true);
$cuentaId = $data['cuentaId'];
$mes = $data['mes'];

if (!is_numeric($cuentaId) || !is_numeric($mes)) {
    http_response_code(400);
    echo "Parámetros inválidos";
    exit;
}

$year = date("Y"); // Año actual
$desde = date("$year-$mes-01");
$hasta = date("Y-m-t", strtotime($desde));

$stmt = $con->prepare("
    SELECT 
        p.Fecha,
        p.Numero AS Poliza,
        b.Nombre AS Beneficiario,
        cu.Numero AS Subcuenta,
        cu.Nombre AS NombreSubcuenta,
        pp.Cargo,
        pp.Abono,
        pp.Observaciones
    FROM conta_partidaspolizas pp
    JOIN cuentas cu ON cu.Id = pp.SubcuentaId
    JOIN conta_polizas p ON p.Id = pp.PolizaId
    LEFT JOIN beneficiarios b ON b.Id = p.BeneficiarioId
    WHERE cu.Id = :cuentaId
    AND p.Fecha BETWEEN :desde AND :hasta
    ORDER BY p.Fecha, p.Numero
");

$stmt->execute([
    ':cuentaId' => $cuentaId,
    ':desde' => $desde,
    ':hasta' => $hasta
]);

$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($registros)) {
    echo "<p class='text-muted'>No hay registros para este mes.</p>";
    exit;
}

echo "<table id='tablaPolizas' class='table table-sm table-striped' style='font-size: 0.80rem;'>";
echo "<thead><tr>
        <th>Fecha</th>
        <th>Póliza</th>
        <th>Beneficiario</th>
        <th>Subcuenta</th>
        <th>Nombre</th>
        <th>Cargo</th>
        <th>Abono</th>
        <th>Observaciones</th>
    </tr></thead><tbody>";

foreach ($registros as $row) {
    $fecha_formateada = date("d/m/Y", strtotime($row['Fecha']));

    echo "<tr>
        <td>{$fecha_formateada}</td>
        <td>{$row['Poliza']}</td>
        <td>{$row['Beneficiario']}</td>
        <td>{$row['Subcuenta']}</td>
        <td>{$row['NombreSubcuenta']}</td>
        <td>$ {$row['Cargo']}</td>
        <td>$ {$row['Abono']}</td>
        <td>{$row['Observaciones']}</td>
    </tr>";
}

echo "</tbody></table>";

