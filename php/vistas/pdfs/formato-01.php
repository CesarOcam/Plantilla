<?php
require('../../../lib/fpdf/fpdf.php'); // Asegúrate de que la ruta sea correcta

include_once('../../modulos/conexion.php');
ob_start();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
$stmt = $con->prepare("
    SELECT 
        r.AduanaId,
        a.nombre_corto_aduana AS nombre_aduana,
        r.Numero,
        r.ClienteExportadorId,
        exp.razonSocial_exportador AS nombre_exportador,
        r.ClienteLogisticoId,
        log.razonSocial_exportador AS nombre_logistico,
        r.Mercancia,
        r.Marcas,
        r.Pedimentos,
        r.ClavePedimento,
        r.PesoBruto,
        r.Cantidad,
        r.Bultos,
        r.Contenedor,
        r.ConsolidadoraId,
        cons.denominacion_consolidadora AS nombre_consolidadora,
        r.ResultadoModulacion,
        CASE 
            WHEN r.Status = 1 THEN 'EN TRÁFICO'
            ELSE 'INACTIVO'
        END AS Status_texto,
        CASE 
            WHEN r.ResultadoModulacion = 1 THEN 'VERDE'
            WHEN r.ResultadoModulacion = 0 THEN 'ROJO'
            ELSE ''
        END AS ResultadoModulacion_texto,
        r.RecintoId,
        rec.inmueble_recintos AS inmueble_recintos,
        r.NavieraId,
        nav.nombre_transportista AS nombre_naviera,
        r.CierreDocumentos,
        r.FechaPago,
        r.BuqueId,
        bq.identificacion AS nombre_buque,
        r.Booking,
        r.CierreDespacho,
        r.HoraDespacho,
        r.Viaje,
        r.SuReferencia,
        r.CierreDocumentado,
        r.LlegadaEstimada,
        r.PuertoDescarga,
        r.PuertoDestino,
        r.Comentarios,
        r.FechaAlta,
        r.Status,
        r.UsuarioAlta,
        CONCAT_WS(' ', u.nombreUsuario, u.apePatUsuario, u.apeMatUsuario) AS nombre_usuario_alta
    FROM conta_referencias r
    LEFT JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
    LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
    LEFT JOIN 01clientes_exportadores log ON r.ClienteLogisticoId = log.id01clientes_exportadores
    LEFT JOIN consolidadoras cons ON r.ConsolidadoraId = cons.id_consolidadora
    LEFT JOIN 2221_recintos rec ON r.RecintoId = rec.id2221_recintos
    LEFT JOIN transportista nav ON r.NavieraId = nav.idtransportista
    LEFT JOIN transporte bq ON r.BuqueId = bq.idtransporte
    LEFT JOIN usuarios u ON r.UsuarioAlta = u.idusuarios
    WHERE r.Id = :id
");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$referencia = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$referencia) {
    die("Error: Referencia no encontrada." . $id);
}
$ref_exp = $referencia['ClienteExportadorId'];
$ref_log = $referencia['ClienteLogisticoId'];

$stmtLogistico = $con->prepare("SELECT * FROM 01clientes_exportadores WHERE id01clientes_exportadores = :id");
$stmtLogistico->bindParam(':id', $ref_log, PDO::PARAM_INT);
$stmtLogistico->execute();
$logistico = $stmtLogistico->fetch(PDO::FETCH_ASSOC);
//País logistico
$paisId = $logistico['id2204clave_pais'];
$stmtPais = $con->prepare("SELECT pais_clave FROM 2204claves_paises WHERE id2204clave_pais = :id");
$stmtPais->bindParam(':id', $paisId, PDO::PARAM_INT);
$stmtPais->execute();
$pais_log = $stmtPais->fetch(PDO::FETCH_ASSOC);
//País exportador
$stmtExportador = $con->prepare("SELECT * FROM 01clientes_exportadores WHERE id01clientes_exportadores = :id");
$stmtExportador->bindParam(':id', $ref_exp, PDO::PARAM_INT);
$stmtExportador->execute();
$exportador = $stmtExportador->fetch(PDO::FETCH_ASSOC);
//País exportador
$paisId = $exportador['id2204clave_pais'];
$stmtPais = $con->prepare("SELECT pais_clave FROM 2204claves_paises WHERE id2204clave_pais = :id");
$stmtPais->bindParam(':id', $paisId, PDO::PARAM_INT);
$stmtPais->execute();
$pais = $stmtPais->fetch(PDO::FETCH_ASSOC);

function toISO($str)
{
    return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
}

$pdf = new FPDF('P', 'mm', 'A4'); // P = vertical, mm = milímetros, A4 = tamaño
$pdf->AddPage(); // Añade una página
// Establecer fuente
$pdf->SetFont('Arial', 'B', size: 9);
$pdf->Image('../../../img/logo2.png', 12, 3, 27); // x, y, ancho

// Agregar un título
$pdf->Cell(0, 0, toISO('AMEXPORT LOGÍSTICA DE MÉXICO, SA. DE CV.'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 8, toISO('Avenida Ricardo Flores Magon No.508'), 0, 1, 'C');
$pdf->Cell(0, 1, toISO('Colonia Ricardo Flores Magon'), 0, 1, 'C');
$pdf->Cell(0, 8, toISO('Veracruz, Ver. C.P.91900'), 0, 1, 'C');
$pdf->Cell(0, 1, toISO('Teléfonos 229-165-0169 y 229-165-0451'), 0, 1, 'C');
$pdf->Cell(0, 8, toISO('RFC: ALM2205042T1'), 0, 1, 'C');

// Coordenadas actuales
$startX = $pdf->GetX();
$startY = $pdf->GetY();
$pdf->SetXY(12, $startY + 2);

// -------- Tabla Facturado Por-------- //
$pdf->SetFillColor(180, 180, 180); // Gris claro
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(142, 4, '', 'LTR', 0, 'L', true);
$pdf->SetXY($startX + 3, $startY + 3);
$pdf->Cell(0, 3, 'FACTURADO A:');

$pdf->SetFillColor(255, 255, 255); // Blanco
$pdf->SetXY(12, $startY + 6);
$pdf->SetFont('Arial', '', 8);
$texto = toISO($logistico['razonSocial_exportador'] . "\n" . $logistico['calle_exportador'] . " - " . $logistico['noExt_exportador'] . ' ' . $logistico['colonia_exportador'] . "\nC.P.:" . $logistico['codigoPostal_exportador'] . ' ' . $logistico['localidad_exportador'] . "\n" . $logistico['municipio_exportador'] . ", " . $pais_log['pais_clave'] . "\n" . $logistico['rfc_exportador']);
$pdf->MultiCell(142, 4.1, $texto, 'LR', 'L');
$pdf->Line(12, $pdf->GetY(), 12 + 142, $pdf->GetY());
// -------- Tabla Facturado Por-------- //

// -------- Tabla Comprobacion de Gastos -------- //
$pdf->SetXY(158, $startY + 2);
$pdf->SetFillColor(180, 180, 180);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(42, 4, '', 'LTR', 1, 'C', true);
$pdf->SetXY($startX + 149, $startY + 3);
$pdf->Cell(0, 3, 'COMPROBACION DE GASTOS');
// Fondo blanco
$pdf->SetFillColor(255, 255, 255);

// Posición inicial
$pdf->SetXY(158, $startY + 6);

// Estilo normal
$pdf->SetFont('Arial', '', 8);

$pdf->Cell(42, 3.2, '', 'LR', 1, 'C');
$pdf->SetXY(158, $startY + 9.3);
$pdf->Cell(42, 3.2, 'FECHA', 'LR', 1, 'C');
$pdf->SetXY(158, $startY + 12.6);
$pdf->Cell(42, 3.7, '02/JUN/2025 12:58', 'LR', 1, 'C');
$pdf->SetXY(158, $startY + 16.5);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(42, 3.3, 'REF:' . $referencia['Numero'], 'LR', 1, 'C');
$pdf->SetXY(158, $startY + 19.9);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(42, 3.3, 'VERACRUZ', 'LR', 1, 'C');
$pdf->SetXY(158, $startY + 23.4);
$pdf->Cell(42, 3.2, '', 'LRB', 1, 'C');
// -------- Tabla Comprobacion de Gastos -------- //

// -------- Tabla Exportador-------- //
$pdf->SetXY(12, $startY + 30);
$pdf->SetFillColor(180, 180, 180);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(188, 4, '', 'LTR', 0, 'L', true);
$pdf->SetXY($startX + 3, $startY + 3);
$pdf->Cell(0, 59, 'EXPORTADOR');

$pdf->SetFillColor(255, 255, 255);
$pdf->SetXY(12, $startY + 34.1);
$pdf->SetFont('Arial', '', 8);
$texto = toISO($exportador['razonSocial_exportador'] . "\n" . $exportador['calle_exportador'] . " - " . $exportador['noExt_exportador'] . ' ' . $exportador['colonia_exportador'] . "\nC.P.:" . $exportador['codigoPostal_exportador'] . ' ' . $exportador['localidad_exportador'] . "\n" . $exportador['municipio_exportador'] . ", " . $pais['pais_clave'] . "\n" . $exportador['rfc_exportador']);
$pdf->MultiCell(188, 4.1, $texto, 'LR', 'L');
$pdf->Line(12, $pdf->GetY(), 12 + 188, $pdf->GetY());
// -------- Tabla Exportador -------- //

// -------- Tabla Mercancía-------- //
$pdf->SetXY(12, $startY + 58);
$pdf->SetFillColor(180, 180, 180); // Gris claro
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(188, 4, '', 'LTR', 0, 'L', true);
$pdf->SetXY($startX + 3, $startY + 31);
$pdf->Cell(0, 59, toISO('IDENTIFICACIÓN DE LA MERCANCÍA EXPORTADA'));

$pdf->SetFillColor(255, 255, 255); // Blanco
$pdf->SetFont('Arial', '', 8);
// Posición inicial
$pdf->SetXY(12, $startY + 62);
// Texto dividido
$col1 = toISO($referencia['Mercancia'] . "\nMARCAS: " . $referencia['Marcas'] . "\nPESO BRUTO: " . $referencia['PesoBruto'] . "\nCVE. PEDIMENTO: " . $referencia['ClavePedimento']);
$col2 = toISO("PEDIMENTO: " . $referencia['Pedimentos'] . "\nFECHA PAGO: " . $referencia['FechaPago'] . "\nBULTOS: " . $referencia['Bultos']);
// Ancho de cada columna
$colWidth = 94; // 188 / 2
$cellHeight = 4.1;
// Guardamos posición Y
$yStart = $pdf->GetY();
// Primera columna
$pdf->SetXY(12, $yStart);
$pdf->MultiCell($colWidth, $cellHeight, $col1, 0, 'L');
// Altura usada por la columna más alta
$col1Height = $pdf->GetY() - $yStart;
// Segunda columna (misma Y de inicio)
$pdf->SetXY(12 + $colWidth, $yStart);
$pdf->MultiCell($colWidth, $cellHeight, $col2, 0, 'L');
// Calculamos la altura usada por la segunda columna
$col2Height = $pdf->GetY() - $yStart;
//Pintar bordes
$maxHeight = max($col1Height, $col2Height);
$pdf->Line(12, $yStart, 12, $yStart + $maxHeight);
$pdf->Line(12 + 188, $yStart, 12 + 188, $yStart + $maxHeight);
$pdf->Line(12, $yStart + $maxHeight, 12 + 188, $yStart + $maxHeight);
// -------- Tabla Mercancía -------- //

// -------- Tabla Informacion Logistica-------- //
$pdf->SetXY(12, $startY + 82);
$pdf->SetFillColor(180, 180, 180); // Gris claro
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(188, 4, '', 'LTR', 0, 'L', true);
$pdf->SetXY($startX + 3, $startY + 55);
$pdf->Cell(0, 59, toISO('INFORMACIÓN LOGÍSTICA'));

$pdf->SetXY(12, $startY + 86.2);
$pdf->SetFont('Arial', '', 8);
$pdf->SetFillColor(255, 255, 255);

// Datos de 5 columnas por 2 filas
$data = [
    [toISO('BOOKING'), toISO('BUQUE'), toISO('PTO. DESCARGA'), toISO('PTO DESTINO'), toISO('SU REFERENCIA')],
    [toISO($referencia['Booking']), toISO($referencia['nombre_buque']), toISO($referencia['PuertoDescarga']), toISO($referencia['PuertoDestino']), toISO($referencia['SuReferencia'])]
];

$xStart = 12;
$yStart = $pdf->GetY();
$colCount = 5;
$tableWidth = 188;
$colWidth = $tableWidth / $colCount;
$rowHeight = 5.5;

$y = $yStart;
foreach ($data as $rowIndex => $row) {
    $x = $xStart;

    if ($rowIndex === 0) {
        $pdf->SetFont('Arial', 'U', 8);
    } else {
        $pdf->SetFont('Arial', '', 8);
    }

    foreach ($row as $cellText) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($colWidth, $rowHeight, $cellText, 0, 0, 'C');
        $x += $colWidth;
    }
    $y += $rowHeight;
}

// Dibujar solo el borde exterior
$tableHeight = $rowHeight * count($data);
$pdf->Line($xStart, $yStart, $xStart, $yStart + $tableHeight);
$pdf->Line($xStart + $tableWidth, $yStart, $xStart + $tableWidth, $yStart + $tableHeight);
$pdf->Line($xStart, $yStart + $tableHeight, $xStart + $tableWidth, $yStart + $tableHeight);
// -------- Tabla Informacion Logistica-------- //

// -------- Tabla Concepto e Importe-------- //
$pdf->SetXY(12, $startY + 100.7);
$pdf->SetFillColor(180, 180, 180); // Gris claro
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(188, 4, '', 'LTR', 0, 'L', true);
$pdf->SetXY($startX + 40, $startY + 31.1);
$pdf->Cell(0, 144, toISO('CONCEPTO'));
$pdf->SetXY($startX + 170, $startY + 31.1);
$pdf->Cell(0, 144, toISO('IMPORTE'));

$stmtPartidas = $con->prepare("SELECT * FROM conta_partidaspolizas WHERE ReferenciaId = :id AND EnKardex != 1 AND CuentaCont !=1");
$stmtPartidas->bindParam(':id', $id, PDO::PARAM_INT);
$stmtPartidas->execute();
$partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

$polizaId = $partidas[0]['PolizaId'];

// --- Recuadro fijo personalizado ---
$boxX = 12;
$boxY = 141;
$boxW = 188;
$boxH = 100;

$pdf->SetFont('Arial', '', 8);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetXY($boxX + 2, $boxY + 2);

$lineHeight = 5;
$maxLines = floor($boxH / $lineHeight);
$lineCount = 0;

$pdf->Line($boxX, $boxY, $boxX, $boxY + $boxH); 
$pdf->Line($boxX + $boxW, $boxY, $boxX + $boxW, $boxY + $boxH);
$pdf->Line($boxX, $boxY + $boxH, $boxX + $boxW, $boxY + $boxH);

$totalLineas = 0;

// --- Cuentas 120 y 123 ---
$subtotal = 0;
foreach ($partidas as $partida) {
    if ($totalLineas >= $maxLines)
        break;

    $subcuentaId = $partida['SubcuentaId'];
    $cargo = $partida['Cargo'];

    $stmtCuenta = $con->prepare("
        SELECT Nombre 
        FROM cuentas 
        WHERE Numero IN (123, 114) AND Id = :id
    ");
    $stmtCuenta->bindParam(':id', $subcuentaId, PDO::PARAM_INT);
    $stmtCuenta->execute();
    $cuenta = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

    if ($cuenta) {
        $nombreCuenta = toISO($cuenta['Nombre']);
        $importe = '$' . number_format($cargo, 2);

        $pdf->SetXY($boxX + 2, $boxY + 2 + ($totalLineas * $lineHeight));
        $pdf->Cell(150, $lineHeight, $nombreCuenta, 0, 0, 'L');

        $pdf->SetXY($boxX + 150, $boxY + 2 + ($totalLineas * $lineHeight));
        $pdf->Cell(36, $lineHeight, $importe, 0, 0, 'R');
        $subtotal += $cargo;
        $totalLineas++;
    }
}

// --- Línea SUBTOTAL ---
$subtotalY = $boxY + 2 + ($totalLineas * $lineHeight) + 1;
$pdf->Line(170, $subtotalY, $boxX + $boxW, $subtotalY);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetXY($boxX + 100, $subtotalY + 1);
$pdf->Cell(57, $lineHeight, 'SUBTOTAL $', 0, 0, 'R');
// Importe del subtotal alineado a la derecha también
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY($boxX + 150, $subtotalY + 1);
$pdf->Cell(36, $lineHeight, '$' . number_format($subtotal, 2), 0, 0, 'R');

// --- Cuentas 214 ---

$totalAnticipos = 0;
foreach ($partidas as $partida) {
    if ($totalLineas >= $maxLines)
        break;

    $subcuentaId = $partida['SubcuentaId'];
    $cargo = $partida['Cargo'];

    $stmtCuenta = $con->prepare("
        SELECT Nombre 
        FROM cuentas 
        WHERE Id = :id AND Numero = 214

    ");
    $stmtCuenta->bindParam(':id', $subcuentaId, PDO::PARAM_INT);
    $stmtCuenta->execute();
    $cuenta = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

    $stmtPoliza = $con->prepare("
        SELECT * 
        FROM conta_polizas 
        WHERE Id = :id
    ");

    $stmtPoliza->bindParam(':id', $polizaId, PDO::PARAM_INT);
    $stmtPoliza->execute();
    $poliza = $stmtPoliza->fetch(PDO::FETCH_ASSOC);
    $fechaAnticipo = $poliza['Fecha'];
    $fechaFormateada = null;

    if (!empty($poliza['Fecha'])) {
        $fecha = new DateTime($poliza['Fecha']);
        $fechaFormateada = $fecha->format('(d-m-y)');
    }

    if ($cuenta) {
        $pdf->SetFont('Arial', '', 8);
        $nombreCuenta = toISO($fechaFormateada . ' ANTICIPO .');
        $importe = '$' . number_format($cargo, 2);

        $lineY = $boxY + 8 + ($totalLineas * $lineHeight);

        $pdf->SetXY($boxX + 126, $lineY);
        $pdf->Cell(150, $lineHeight, $nombreCuenta, 0, 0, 'L');

        $pdf->SetXY($boxX + 150, $lineY);
        $pdf->Cell(36, $lineHeight, $importe, 0, 0, 'R');
        $totalAnticipos += $cargo;
        $totalLineas++;
    }

}
$saldo = $subtotal - $totalAnticipos;

// --- Línea SALDOS debajo de los ANTICIPOS ---
$saldosY = $boxY + 8 + ($totalLineas * $lineHeight) + 1;
$pdf->Line(170, $saldosY, $boxX + $boxW, $saldosY);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetXY($boxX + 100, $saldosY + 1);
$pdf->Cell(57, $lineHeight, 'SALDO $', 0, 0, 'R');
// Valor del saldo alineado a la derecha junto a SALDO $
$pdf->SetXY($boxX + 150, $saldosY + 1); // ← MISMA Y
$pdf->Cell(36, $lineHeight, '$' . number_format($saldo, 2), 0, 0, 'R');

ob_end_clean();
$pdf->Output();
?>