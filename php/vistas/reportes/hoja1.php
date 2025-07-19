<?php
require '../../../lib/SpredSheet/vendor/autoload.php';
require_once('../../modulos/conexion.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Crear nuevo objeto Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$headers = ['Póliza', 'Logístico', 'Num.Sub.', 'Subcuenta', 'Pagos', 'Importe', 'IVA', 'Subtotal', 'Anticipo', 'Saldo', 'Num.Factura', 'Status', 'Fecha de Pago'];
$col = 'A';

foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($col . '1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Consulta SQL con JOINs
$sql = "
SELECT 
    p.Id AS PolizaId,
    p.Numero AS PolizaNumero,
    p.Importe,
    part.Observaciones,
    r.Numero AS ReferenciaNumero,
    c.razonSocial_exportador AS Beneficiario,
    a.nombre_corto_aduana AS NombreAduana,
    (
        SELECT SubcuentaId 
        FROM partidaspolizas pp2 
        WHERE pp2.PolizaId = p.Id 
        ORDER BY pp2.Id DESC 
        LIMIT 1
    ) AS UltimoNumSub,
    (
        SELECT Cargo 
        FROM partidaspolizas pp3 
        WHERE pp3.PolizaId = p.Id AND pp3.SubcuentaId = 133 
        LIMIT 1
    ) AS Anticipo,
    (
        SELECT cu.Numero 
        FROM partidaspolizas pp4 
        JOIN cuentas cu ON cu.Id = pp4.SubcuentaId 
        WHERE pp4.PolizaId = p.Id 
        ORDER BY pp4.Id DESC 
        LIMIT 1
    ) AS SubcuentaNumero
FROM polizas p
LEFT JOIN partidaspolizas part ON part.PolizaId = p.Id
LEFT JOIN referencias r ON r.Id = part.ReferenciaId
LEFT JOIN 01clientes_exportadores c ON c.id01clientes_exportadores = r.ClienteLogisticoId
LEFT JOIN 2201aduanas a ON a.id2201aduanas = r.AduanaId
WHERE p.Numero LIKE 'D%'
GROUP BY p.Id
";


// Ejecutar consulta
$stmt = $con->prepare($sql);
$stmt->execute();
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rellenar filas
$fila = 2;
foreach ($datos as $dato) {
    $sheet->setCellValue('A' . $fila, $dato['PolizaNumero']);
    $sheet->setCellValue('B' . $fila, $dato['ReferenciaNumero']);
    $sheet->setCellValue('C' . $fila, $dato['Beneficiario']);

    // Subcuenta (D) alineada a la derecha
    $sheet->setCellValue('D' . $fila, $dato['SubcuentaNumero']);
    $sheet->getStyle('D' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    $sheet->setCellValue('E' . $fila, $dato['NombreAduana']);

    // Cargo (F) con formato numérico
    $sheet->setCellValue('F' . $fila, (float) $dato['Cargo']);
    $sheet->getStyle('F' . $fila)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');
    $sheet->getStyle('F' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Cargo (F) con formato numérico
    $sheet->setCellValue('G' . $fila, (float) $dato['Importe']);
    $sheet->getStyle('G' . $fila)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');
    $sheet->getStyle('G' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    $sheet->setCellValue('H' . $fila, '0.00');
    $sheet->getStyle('H' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    $importe = isset($dato['Importe']) ? (float) $dato['Importe'] : 0;
    $cargo = isset($dato['Cargo']) ? (float) $dato['Cargo'] : 0;
    $total = $importe + $cargo;

    $sheet->setCellValue('I' . $fila, $total);
    $sheet->getStyle('I' . $fila)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');
    $sheet->getStyle('I' . $fila)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    $fila++;
}


// Guardar archivo
$writer = new Xlsx($spreadsheet);
$filename = 'polizas_exportadas.xlsx';
$writer->save($filename);

echo "Archivo generado correctamente: $filename";
