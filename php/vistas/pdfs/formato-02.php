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
        cp.claveCve AS clave_pedimento_cve,
        r.PesoBruto,
        r.Cantidad,
        r.Bultos,
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
        rec.nombre_conocido_recinto AS inmueble_recintos,
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
        r.FechaAlta,
        CONCAT(u.nombreUsuario, ' ', u.apePatUsuario, ' ', u.apeMatUsuario) AS nombre_usuario_alta
    FROM referencias r
    LEFT JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
    LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
    LEFT JOIN 01clientes_exportadores log ON r.ClienteLogisticoId = log.id01clientes_exportadores
    LEFT JOIN consolidadoras cons ON r.ConsolidadoraId = cons.id_consolidadora
    LEFT JOIN 2206_recintos_fiscalizados rec ON r.RecintoId = rec.id2206_recintos_fiscalizados
    LEFT JOIN transportista nav ON r.NavieraId = nav.idtransportista
    LEFT JOIN transporte bq ON r.BuqueId = bq.idtransporte
    LEFT JOIN usuarios u ON r.UsuarioAlta = u.idusuarios
    LEFT JOIN 2202clavepedimento cp ON r.ClavePedimento = cp.id2202clave_pedimento -- <--- JOIN con clavepedimento
    WHERE r.Id = :id

");

$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$referencia = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$referencia) {
    die("Error: Referencia no encontrada." . $id);
}

$stmtExportador = $con->prepare("SELECT * FROM 01clientes_exportadores WHERE id01clientes_exportadores = :id");
$stmtExportador->bindParam(':id', $id, PDO::PARAM_INT);
$stmtExportador->execute();
$exportador = $stmtExportador->fetch(PDO::FETCH_ASSOC);

$ref_exp = $referencia['ClienteExportadorId'];
//País logístico
$stmtLogistico = $con->prepare("SELECT * FROM 01clientes_exportadores WHERE id01clientes_exportadores = :id");
$stmtLogistico->bindParam(':id', $ref_exp, PDO::PARAM_INT);
$stmtLogistico->execute();
$logistico = $stmtLogistico->fetch(PDO::FETCH_ASSOC);


//Obtener país
$paisId = $exportador['id2204clave_pais'];
$stmtPais = $con->prepare("SELECT pais_clave FROM 2204claves_paises WHERE id2204clave_pais = :id");
$stmtPais->bindParam(':id', $paisId, PDO::PARAM_INT);
$stmtPais->execute();
$pais = $stmtPais->fetch(PDO::FETCH_ASSOC);




$sql = "SELECT codigo FROM contenedores WHERE referencia_id = :id";
$stmt = $con->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$contenedores = $stmt->fetchAll(PDO::FETCH_COLUMN);
$contenedoresStr = implode(', ', $contenedores);





//--------------------------------------FIN DE LAS CONSULTAS-----------------------------------------------------------------//

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

//-----------------------------------------------------------------------------------------------------------------//
$pdf->SetFont('Arial', '', 16);
$pdf->Cell(0, 13, toISO($referencia['clave_pedimento_cve']), 0, 1, 'R');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 2, toISO('REFERENCIA No: ' . $referencia['Numero']), 0, 1, 'R');
$pdf->Cell(0, 10, toISO('PEDIMENTOS: ' . $referencia['Pedimentos']), 0, 1, 'R');
//-----------------------------------------------------------------------------------------------------------------//

//-----------------------------------------------------------------------------------------------------------------//

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 13, toISO('EXPORTADOR:'), 0, 1, 'L');
$startX = $pdf->GetX();
$startY = $pdf->GetY();

$pdf->SetXY(38, $startY + -7.4);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 2, toISO($referencia['nombre_exportador']), 0, 1, 'L');

$pdf->SetXY(10, $startY + 3);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 2, toISO('FACTURAR A: '), 0, 1, 'L');
$startX = $pdf->GetX();
$startY = $pdf->GetY();

$pdf->SetXY(36, $startY + -2.4);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 2, toISO($referencia['nombre_logistico']), 0, 1, 'L');

// Posiciones X para las 4 columnas
$col1X = 10;   // Etiqueta 1
$col2X = 48;   // Valor 1
$col3X = 100;  // Etiqueta 2
$col4X = 140;  // Valor 2

$colY = $startY + 15;  // Altura inicial
$lineHeight = 8;       // Altura de línea de texto
$marginAfter = 3;      // Espacio vertical entre filas

// Anchos de celdas
$labelWidth = 40;
$valueWidth = 60;

$pdf->SetFont('Arial', 'B', 10);

// Fila 1
$pdf->SetXY(10, $startY + 8);
$pdf->Cell(0, 2, toISO('BOOKING:'), 0, 1, 'L');
$pdf->SetXY(29, $startY + 8);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 2, toISO($referencia['Booking'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $startY + 8);
$pdf->Cell(0, 2, toISO('BUQUE:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(117, $startY + 8);
$pdf->Cell(0, 2, toISO($referencia['nombre_buque'] ?? ''), 0, 1, 'L');

// Fila 2
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY - 2);
$pdf->Cell(0, 2, toISO('VIAJE:'), 0, 1, 'L');
$pdf->SetXY(23, $colY - 2);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 2, toISO($referencia['Viaje'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY - 2);
$pdf->Cell(0, 2, toISO('CANTIDAD:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(123, $colY - 2);
$pdf->Cell(0, 2, toISO($referencia['Cantidad'] ?? ''), 0, 1, 'L');

// Fila 3
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY + 3);
$pdf->Cell(0, 2, toISO('CONTENEDOR:'), 0, 1, 'L');
$pdf->SetXY(37, $colY + 2);
$pdf->SetFont('Arial', '', 9.5);
$pdf->MultiCell(60, 4, toISO($contenedoresStr), 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY + 3);
$pdf->Cell(0, 2, toISO('PESO BRUTO:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(128, $colY + 3);
$pdf->Cell(0, 2, toISO($referencia['PesoBruto'] ?? ''), 0, 1, 'L');

// Fila 4
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 8);
$pdf->Cell(0, 2, toISO('MERCANCIAS:'), 0, 1, 'L');
$pdf->SetXY(36, $colY  + 7);
$pdf->SetFont('Arial', '', 9.5);
$pdf->MultiCell(60, 4, toISO($referencia['Mercancia'] ), 0, 'L');

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 8);
$pdf->Cell(0, 2, toISO('MARCAS:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(120, $colY  + 8);
$pdf->Cell(0, 2, toISO($referencia['Marcas'] ?? ''), 0, 1, 'L');

// Fila 5
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 13);
$pdf->Cell(0, 2, toISO('ADUANA:'), 0, 1, 'L');
$pdf->SetXY(28, $colY  + 13);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 2, toISO($referencia['nombre_aduana'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 13);
$pdf->Cell(0, 2, toISO('RECINTO:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(120, $colY  + 13);
$pdf->Cell(0, 2, toISO($referencia['inmueble_recintos'] ?? ''), 0, 1, 'L');

// Fila 6
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 18);
$pdf->Cell(0, 2, toISO('NAVIERA:'), 0, 1, 'L');
$pdf->SetXY(28, $colY  + 18);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 2, toISO($referencia['nombre_naviera'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 18);
$pdf->Cell(0, 2, toISO('CONSOLIDADORA:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(136, $colY  + 17);
$pdf->MultiCell(60, 4, toISO($referencia['nombre_consolidadora'] ), 0, 'L');

// Fila 7
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 24);
$pdf->Cell(0, 2, toISO('PUERTO DESCARGA:'), 0, 1, 'L');
$pdf->SetXY(48, $colY  + 24);
$pdf->SetFont('Arial', '',  9.5);
$pdf->Cell(0, 2, toISO($referencia['PuertoDescarga'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 24);
$pdf->Cell(0, 2, toISO('DESTINO FINAL:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(132, $colY  + 24);
$pdf->Cell(0, 2, toISO($referencia['PuertoDestino'] ?? ''), 0, 1, 'L');

// Fila 8
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 30);
$pdf->Cell(0, 2, toISO('CIERRE DOCUMENTOS:'), 0, 1, 'L');
$pdf->SetXY(52, $colY  + 30);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 2, toISO($referencia['CierreDocumentos'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 30);
$pdf->Cell(0, 2, toISO('CIERRE DESPACHO:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetXY(140, $colY  + 30);
$pdf->Cell(0, 2, toISO($referencia['CierreDespacho'] ?? ''), 0, 1, 'L');

// Fila 9
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 35);
$pdf->Cell(0, 2, toISO('FECHA DOCUMENTADO:'), 0, 1, 'L');
$pdf->SetXY(54, $colY  + 35);
$pdf->SetFont('Arial', '', 9.5);
$pdf->Cell(0, 2, toISO($referencia['CierreDocumentado'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 35);
$pdf->Cell(0, 2, toISO('HORA DESPACHO:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', size: 9.5);
$pdf->SetXY(136, $colY  + 35);
$pdf->Cell(0, 2, toISO($referencia['HoraDespacho'] ?? ''), 0, 1, 'L');

// Fila 10
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 40);
$pdf->Cell(0, 2, toISO('ETA:'), 0, 1, 'L');
$pdf->SetXY(20, $colY  + 40);
$pdf->SetFont('Arial', '', size: 9.5);
$pdf->Cell(0, 2, toISO($referencia['LlegadaEstimada'] ?? ''), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(102, $colY  + 40);
$pdf->Cell(0, 2, toISO('REFERENCIA EXTERNA:'), 0, 1, 'L');
$pdf->SetFont('Arial', '', size: 9.5);
$pdf->SetXY(145, $colY  + 40);
$pdf->Cell(0, 2, toISO($referencia['SuReferencia'] ?? ''), 0, 1, 'L');

// Fila 11
$colY += 6;
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(10, $colY  + 45);
$pdf->Cell(0, 2, toISO('COMENTARIOS:'), 0, 1, 'L');
$pdf->SetXY(40, $colY  + 45);
$pdf->SetFont('Arial', '',  10);
$pdf->Cell(0, 2, toISO($referencia['Comentarios'] ?? ''), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 10);
$colY += 70;
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(65, $colY  + 45);
$pdf->Cell(0, 2, toISO('FECHA Y HORA DE ALTA: ' . $referencia['FechaAlta'] ?? '' ), 0, 1, 'L');

// --- Calcular alturas para las 4 celdas usando MultiCell sin mover el cursor definitivo ---

// Función auxiliar para calcular altura de MultiCell (sin imprimir realmente)
function getMultiCellHeightSafe($pdf, $width, $text, $lineHeight)
{
    $cw = $pdf->GetStringWidth($text);
    // Para aproximar líneas, mejor dividir el texto por líneas reales y contar:
    $numLines = $pdf->GetStringWidth($text) / $width;
    // Pero mejor usar MultiCell en un buffer o un clon para medir:
    // Como es complejo, una aproximación es:
    $nbLines = 0;
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        $nbLines += max(1, ceil($pdf->GetStringWidth($line) / $width));
    }
    return $nbLines * $lineHeight;
}

foreach ($filas as $fila) {
    // Guarda la posición inicial Y para esta fila
    $startYrow = $colY;

    $pdf->SetFont('Arial', 'B', 10);
    $heightLabel1 = getMultiCellHeightSafe($pdf, $labelWidth, toISO($fila[0]), $lineHeight);
    $heightLabel2 = !empty($fila[2]) ? getMultiCellHeightSafe($pdf, $labelWidth, toISO($fila[2]), $lineHeight) : 0;

    $pdf->SetFont('Arial', '', 10);
    $heightValue1 = getMultiCellHeightSafe($pdf, $valueWidth, toISO($fila[1]), $lineHeight);
    $heightValue2 = !empty($fila[3]) ? getMultiCellHeightSafe($pdf, $valueWidth, toISO($fila[3]), $lineHeight) : 0;

    // Calcula la altura máxima para esta fila
    $maxHeight = max($heightLabel1, $heightValue1, $heightLabel2, $heightValue2);

    // --- Imprimir las 4 celdas con MultiCell, en la misma Y (startYrow) ---
    // Etiqueta 1 (bold)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY($col1X, $startYrow);
    $pdf->MultiCell($labelWidth, $lineHeight, toISO($fila[0]), 0, 'L');

    // Valor 1 (normal)
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY($col2X, $startYrow);
    $pdf->MultiCell($valueWidth, $lineHeight, toISO($fila[1]), 0, 'L');

    // Etiqueta 2 (bold)
    if (!empty($fila[2])) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($col3X, $startYrow);
        $pdf->MultiCell($labelWidth, $lineHeight, toISO($fila[2]), 0, 'L');
    }

    // Valor 2 (normal)
    if (!empty($fila[3])) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY($col4X, $startYrow);
        $pdf->MultiCell($valueWidth, $lineHeight, toISO($fila[3]), 0, 'L');
    }

    // Avanzar Y para la siguiente fila con margen
    $colY += $maxHeight + $marginAfter;
}


//-----------------------------------------------------------------------------------------------------------------//

ob_end_clean();
$pdf->Output();
?>