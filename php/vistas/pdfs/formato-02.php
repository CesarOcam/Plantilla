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
$pdf->SetFont('Arial', 'B', size: 13);
$pdf->Image('../../../img/logo2.png', 12, 3, 27); // x, y, ancho

// Agregar un título
$pdf->Cell(0, 0, toISO('ASOCIACION MEXICANA DE EXPORTADORES, A.C.'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 13, toISO('CONTROL DE REFERENCIAS'), 0, 1, 'C');

// Coordenadas actuales
$startX = $pdf->GetX();
$startY = $pdf->GetY();
$pdf->SetXY(12, $startY + 2);

//-----------------------------------------------------------------------------------------------------------------//
$pdf->SetFont('Arial', '', 16);
$pdf->Cell(0, 13, toISO($referencia['ClavePedimento']), 0, 1, 'R');
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
$pdf->SetXY(10, $startY + 2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 2, toISO('FACTURAR A:' . $logistico['razonSocial_exportador']), 0, 1, 'L');
//-----------------------------------------------------------------------------------------------------------------//

ob_end_clean();
$pdf->Output();
?>