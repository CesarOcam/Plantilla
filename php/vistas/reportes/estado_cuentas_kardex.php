<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../../../lib/SpredSheet/vendor/autoload.php';
require_once('../../modulos/conexion.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Row 1: Nombre de la empresa (centrado y unido de C a F)
$sheet->mergeCells('C1:F1');
$sheet->setCellValue('C1', 'AMEXPORT LOGÍSTICA DE MÉXICO, SA. DE CV');
$sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Row 2: Estado de cuenta (C a F)
$sheet->mergeCells('C2:F2');
$sheet->setCellValue('C2', 'ESTADO DE CUENTA');
$sheet->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Row 3: Fecha actual en G
$sheet->setCellValue('G3', 'Fecha: ' . date('d/m/Y'));
$sheet->getStyle('G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Row 4: Hora actual en G
// Establecer zona horaria
date_default_timezone_set('America/Mexico_City');

// Row 4: Hora actual en G
$sheet->setCellValue('G4', 'Hora: ' . date('H:i:s'));
$sheet->getStyle('G4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);


// Preparar parámetros
$where = [];
$params = [];
if (!empty($_GET['logistico'])) {
    $where[] = "le.razonSocial_exportador LIKE :logistico";
    $params[':logistico'] = "%" . $_GET['logistico'] . "%";
}
$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Consulta
$sql = "
SELECT 
    c.Id,
    c.NumCg,
    r.Numero AS ReferenciaNumero,
    r.AduanaId,
    b.identificacion AS BuqueNombre,
    le.razonSocial_exportador AS LogisticoNombre,
    ee.razonSocial_exportador AS ExportadorNombre,
    c.Fecha,
    c.Booking,
    c.SuReferencia,
    c.Saldo
FROM conta_cuentas_kardex c
LEFT JOIN conta_referencias r 
    ON c.Referencia = r.Id
LEFT JOIN transporte b 
    ON c.Barco = b.idtransporte
LEFT JOIN 01clientes_exportadores le 
    ON c.Logistico = le.id01clientes_exportadores
LEFT JOIN 01clientes_exportadores ee 
    ON c.Exportador = ee.id01clientes_exportadores
LEFT JOIN 2201aduanas a 
    ON r.AduanaId = a.id2201aduanas
$whereSql AND c.Status != 2
ORDER BY c.Fecha ASC
";

$stmt = $con->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$kardex = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Row 5: Cliente (C a F) - tomamos el primer registro si existe
$clienteNombre = !empty($kardex[0]['LogisticoNombre']) ? $kardex[0]['LogisticoNombre'] : '';
$sheet->mergeCells('C5:F5');
$sheet->setCellValue('C5', 'Cliente: ' . $clienteNombre);
$sheet->getStyle('C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Row 7: Encabezados de la tabla
$headers = ['C.GTOS.', 'REF', 'EXPORTADOR', 'FECHA', 'BOOKING', 'BARCO', 'SU REFERENCIA', 'SALDO'];
$col = 'A';
$rowHeaders = 7;
foreach ($headers as $header) {
    $cell = $col . $rowHeaders;
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
    $sheet->getStyle($cell)->getFill()->getStartColor()->setRGB('6B7A9C');
    $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
    $col++;
}

// Rellenar filas debajo de la cabecera (row 8 en adelante)
$fila = 8;
$totalSaldo = 0; // acumulador

foreach ($kardex as $dato) {
    $sheet->setCellValue('A' . $fila, $dato['NumCg']);
    $sheet->setCellValue('B' . $fila, $dato['ReferenciaNumero']);
    $sheet->setCellValue('C' . $fila, $dato['ExportadorNombre']);
    $sheet->setCellValue('D' . $fila, $dato['Fecha']);
    $sheet->setCellValue('E' . $fila, $dato['Booking']);
    $sheet->setCellValue('F' . $fila, $dato['BuqueNombre']);
    $sheet->setCellValue('G' . $fila, $dato['SuReferencia']);
    $sheet->setCellValue('H' . $fila, $dato['Saldo']);

    // acumular saldo
    $totalSaldo += (float) $dato['Saldo'];

    $fila++;
}

// Fila de TOTAL
$sheet->mergeCells('A' . $fila . ':G' . $fila);
$sheet->setCellValue('A' . $fila, 'TOTAL');
$sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('A' . $fila)->getFont()->setBold(true);

// Total en columna H
$sheet->setCellValue('H' . $fila, $totalSaldo);
$sheet->getStyle('H' . $fila)->getFont()->setBold(true);
$sheet->getStyle('H' . $fila)->getNumberFormat()->setFormatCode('#,##0.00'); // formato con comas y 2 decimales

// Descargar Excel
$nombreArchivo = 'estado_de_cuenta.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header('Cache-Control: max-age=0');
ob_clean();
flush();

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
