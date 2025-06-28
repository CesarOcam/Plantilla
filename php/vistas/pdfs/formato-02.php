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
        nav.identificacion AS nombre_naviera,
        r.CierreDocumentos,
        r.FechaPago,
        r.BuqueId,
        bq.Nombre AS nombre_buque,
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
        CONCAT(u.nombreUsuario, ' ', u.apePatUsuario, ' ', u.apeMatUsuario) AS nombre_usuario_alta
    FROM referencias r
    LEFT JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
    LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
    LEFT JOIN 01clientes_exportadores log ON r.ClienteLogisticoId = log.id01clientes_exportadores
    LEFT JOIN consolidadoras cons ON r.ConsolidadoraId = cons.id_consolidadora
    LEFT JOIN 2221_recintos rec ON r.RecintoId = rec.id2221_recintos
    LEFT JOIN transporte nav ON r.NavieraId = nav.idtransporte
    LEFT JOIN con_buques bq ON r.BuqueId = bq.Id
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

$pdf->SetXY(10, $startY + 5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 2, toISO('FACTURAR A: '), 0, 1, 'L');
$startX = $pdf->GetX();
$startY = $pdf->GetY();

$pdf->SetXY(38, $startY + -2.4);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 2, toISO($referencia['nombre_logistico']), 0, 1, 'L');

// Posiciones X para las 4 columnas
$col1X = 10;   // Etiqueta 1
$col2X = 48;   // Valor 1
$col3X = 100;  // Etiqueta 2
$col4X = 140;  // Valor 2

$colY = $startY + 8;  // Altura inicial
$lineHeight = 8;       // Altura de línea de texto
$marginAfter = 3;      // Espacio vertical entre filas

// Anchos de celdas
$labelWidth = 40;
$valueWidth = 60;

// Datos: [Etiqueta1, Valor1, Etiqueta2, Valor2]
$filas = [
    ['BOOKING:', $referencia['Booking'] ?? '', 'BUQUE:', $referencia['nombre_buque'] ?? ''],
    ['VIAJE:', $referencia['Viaje'] ?? '', 'CANTIDAD:', $referencia['Cantidad'] ?? ''],
    ['CONTENEDOR:', $referencia['Contenedor'] ?? '', 'PESO BRUTO:', $referencia['PesoBruto'] ?? ''],
    ['MERCANCIAS:', $referencia['Mercancia'] ?? '', 'MARCAS:', $referencia['Marcas'] ?? ''],
    ['ADUANA:', $referencia['nombre_aduana'] ?? '', 'RECINTO:', $referencia['inmueble_recintos'] ?? ''],
    ['NAVIERA:', $referencia['nombre_naviera'] ?? '', 'CONSOLIDADORA:', $referencia['nombre_consolidadora'] ?? ''],
    ['PUERTO DESCARGA:', $referencia['PuertoDescarga'] ?? '', 'DESTINO FINAL:', $referencia['PuertoDestino'] ?? ''],
    ['CIERRE DOCUMENTOS:', $referencia['CierreDocumentos'] ?? '', 'CIERRE DESPACHO:', $referencia['CierreDespacho'] ?? ''],
    ['FECHA DOCUMENTADO:', $referencia['CierreDocumentado'] ?? '', 'HORA DESPACHO:', $referencia['HoraDespacho'] ?? ''],
    ['ETA:', $referencia['LlegadaEstimada'] ?? '', '', ''],
    ['COMENTARIOS:', $referencia['Comentarios'] ?? '', '', ''],
];

$pdf->SetFont('Arial', 'B', 10);

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