<?php
error_reporting(0);
ini_set('display_errors', 0);
require '../../../lib/SpredSheet/vendor/autoload.php';
require_once('../../modulos/conexion.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Crear nuevo objeto Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$headers = ['Fecha', 'Póliza', 'Beneficiario', 'Num.Sub', 'Subcuenta', 'Cargo', 'Abono', 'Observaciones'];
$col = 'A';

foreach ($headers as $header) {
    $cell = $col . '1';

    // Texto del encabezado
    $sheet->setCellValue($cell, $header);

    // Alineación centrada
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    // Tamaño automático de la columna
    $sheet->getColumnDimension($col)->setAutoSize(true);

    // Color de fondo: rgb(107, 122, 156) -> hex 6B7A9C
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
    $sheet->getStyle($cell)->getFill()->getStartColor()->setRGB('6B7A9C');

    // (Opcional) Color del texto blanco para mejor contraste
    $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');

    $col++;
}

$cuentaId = $_POST['cuentaId'] ?? null;
$desde = $_POST['fechaDesdeInput'] ?? null;
$hasta = $_POST['fechaHastaInput'] ?? null;

// 2. Validar que vengan los valores necesarios
if (!$cuentaId || !$desde || !$hasta) {
    die("Faltan datos para generar el reporte");
}

// Consulta SQL con JOINs
$sql = "
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
ORDER BY p.Fecha, p.Numero";


$stmt = $con->prepare($sql);
$stmt->execute([
    ':cuentaId' => $cuentaId,
    ':desde' => $desde,
    ':hasta' => $hasta
]);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rellenar filas
$fila = 2;
foreach ($datos as $dato) {
    $sheet->setCellValue('A' . $fila, $dato['Fecha']);
    $sheet->setCellValue('B' . $fila, $dato['Poliza']);
    $sheet->setCellValue('C' . $fila, $dato['Beneficiario']);

    // Subcuenta (D) alineada a la derecha
    $sheet->setCellValue('D' . $fila, $dato['Subcuenta']);
    $sheet->getStyle('D' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    $sheet->setCellValue('E' . $fila, $dato['NombreSubcuenta']);

    // Cargo (F) con formato numérico
    $sheet->setCellValue('F' . $fila, (float) $dato['Cargo']);
    $sheet->getStyle('F' . $fila)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');
    $sheet->getStyle('F' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Cargo (F) con formato numérico
    $sheet->setCellValue('G' . $fila, (float) $dato['Abono']);
    $sheet->getStyle('G' . $fila)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');
    $sheet->getStyle('G' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    $sheet->setCellValue('H' . $fila,  $dato['Observaciones']);
    $sheet->getStyle('H' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $fila++;
}

$nombreArchivo = 'polizas_exportadas.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header('Cache-Control: max-age=0');

ob_clean(); // Limpia el buffer
flush();    // Asegura que se envíe limpio

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
