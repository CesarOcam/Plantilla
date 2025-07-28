<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
include_once(__DIR__ . '/../conexion.php');


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT s.Id, s.ReferenciaFacturaId, e.Nombre AS EmpresaNombre, b.Nombre AS BeneficiarioNombre, s.Importe, s.Fecha, s.FechaAlta, a.nombre_corto_aduana AS AduanaNombre
            FROM conta_solicitudes s
            JOIN empresas e ON s.EmpresaId = e.Id
            JOIN beneficiarios b ON s.BeneficiarioId = b.Id
            JOIN 2201aduanas a ON s.Aduana = a.id2201aduanas
            WHERE s.Id = :id LIMIT 1";

    $stmt = $con->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($solicitud) {
        // Obtener partidas relacionadas
        $sqlPartidas = "SELECT 
            CONCAT(c.Numero, ' - ', c.Nombre) AS SubcuentaNombre,
            ps.Importe, 
            ps.Observaciones, 
            ps.NumeroFactura,
            r.Numero AS ReferenciaNumero,
            ce.razonSocial_exportador AS RazonSocialExportador
        FROM conta_partidassolicitudes ps
        JOIN cuentas c ON ps.SubcuentaId = c.Id
        LEFT JOIN conta_referencias r ON ps.ReferenciaId = r.Id
        LEFT JOIN 01clientes_exportadores ce ON r.ClienteExportadorId = ce.id01clientes_exportadores
        WHERE ps.SolicitudId = :id";

        $stmt2 = $con->prepare($sqlPartidas);
        $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt2->execute();
        $partidas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $solicitud['Partidas'] = $partidas;

        echo json_encode($solicitud);
    } else {
        echo json_encode(['error' => 'Solicitud no encontrada']);
    }
} else {
    echo json_encode(['error' => 'No se proporcionó ID']);
}
