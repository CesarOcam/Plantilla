<?php
header('Content-Type: application/json; charset=utf-8');
include('../conexion.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Consulta por aduanas y número de operaciones
$sql = "
    SELECT COALESCE(a.nombre_corto_aduana, 'SIN ADUANA') AS Aduana, COUNT(r.Id) AS Operaciones
    FROM conta_referencias r
    LEFT JOIN 2201aduanas a ON a.id2201aduanas = r.AduanaId
    GROUP BY a.nombre_corto_aduana
    ORDER BY a.nombre_corto_aduana
";

$stmt = $con->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar arrays para Chart.js
$labels = [];
$data = [];

foreach ($result as $row) {
    $labels[] = $row['Aduana'];
    $data[] = (int)$row['Operaciones'];
}

if (!$result) {
    echo json_encode(['error' => $stmt->errorInfo()]);
    exit;
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);
