<?php
session_start();
ob_start(); 
include('../conexion.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../../lib/mailer/vendor/autoload.php';
require '../../vistas/pdfs/cuenta_gastos_mail.php';

try {
    header('Content-Type: application/json');

    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit;
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
        exit;
    }

    $id = $_POST['id'];
    $referenciaId = $id;

    $stmt = $con->prepare("
            SELECT 
                r.AduanaId,
                a.nombre_corto_aduana AS nombre_aduana,
                r.Numero,
                r.ClienteExportadorId,
                r.ClienteLogisticoId,
                exp.razonSocial_exportador AS nombre_exportador,
                exp2.razonSocial_exportador AS nombre_logistico,
                r.BuqueId,
                bq.identificacion AS nombre_buque,
                r.Booking,
                r.SuReferencia,
                r.PuertoDescarga,
                r.PuertoDestino,
                r.UsuarioAlta,
                CONCAT(u.nombreUsuario, ' ', u.apePatUsuario, ' ', u.apeMatUsuario) AS nombre_usuario_alta
            FROM conta_referencias r
            LEFT JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
            LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
            LEFT JOIN 01clientes_exportadores exp2 ON r.ClienteExportadorId = exp.id01clientes_exportadores
            LEFT JOIN transporte bq ON r.BuqueId = bq.idtransporte
            LEFT JOIN usuarios u ON r.UsuarioAlta = u.idusuarios
            WHERE r.Id = :id
        ");
    $stmt->bindParam(':id', $referenciaId, PDO::PARAM_INT);
    $stmt->execute();
    $referencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $con->prepare("SELECT nombre FROM beneficiarios WHERE id = :id");
    $stmt->bindParam(':id', $beneficiario, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $nombreBeneficiario = $row ? $row['nombre'] : 'Desconocido';
    $fechaFormateada = date('d/m/Y', strtotime($fecha));

    $aduana = $referencia['nombre_aduana'];
    $numero = $referencia['Numero'];
    $clienteId = $referencia['ClienteExportadorId'];
    $nombreExportador = $referencia['nombre_exportador'] ?? 'N/A';
    $nombreLogistico = $referencia['nombre_logistico'] ?? 'N/A';
    $buqueId = $referencia['BuqueId'];
    $nombreBuque = $referencia['nombre_buque'];
    $booking = $referencia['Booking'];
    $suReferencia = $referencia['SuReferencia'];
    $puertoDescarga = $referencia['PuertoDescarga'];
    $puertoDestino = $referencia['PuertoDestino'];
    $usuarioAltaId = $referencia['UsuarioAlta'];
    $usuarioAltaRef = $referencia['nombre_usuario_alta'];

    $pdfContent = generarPDFCuentaGastos($referenciaId);

    $resultado = true;

    if ($resultado) {
        try {
            $mail = new PHPMailer(true);

            // Configuración SMTP...
            $mail->isSMTP();
            $mail->Host = 'smtp.mail.us-east-1.awsapps.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'notificaciones@grupoamexport.com';
            $mail->Password = 'AMEXPORT.2024';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('notificaciones@grupoamexport.com', 'Notificaciones Amexport');
            $mail->addAddress('jesus.reyes@grupoamexport.com');
            $mail->addAddress('cesar.pulido@grupoamexport.com');
            $mail->AddEmbeddedImage('../../../img/Amexport.jpeg', 'logoimage');
            $mail->AddEmbeddedImage('../../../img/LogoAmex.png', 'logoAmex');
            //$mail->addStringAttachment($pdfContent, "cuenta_gastos_$referenciaId.pdf");

            // Crear archivo ZIP temporal
            $zipFile = tempnam(sys_get_temp_dir(), "$suReferencia . $referenciaId . $nombreExportador") . '.zip';
            $zip = new ZipArchive();

            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                // Agregar el PDF generado
                $zip->addFromString("cuenta_gastos_$referenciaId.pdf", $pdfContent);

                // Agregar los archivos de la tabla
                $stmt = $con->prepare("SELECT nombre, ruta FROM conta_referencias_archivos WHERE Referencia_id = :id");
                $stmt->bindParam(':id', $referenciaId, PDO::PARAM_INT);
                $stmt->execute();
                $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($archivos as $archivo) {
                    $rutaArchivo = $archivo['ruta'];
                    $nombreArchivo = $archivo['nombre'] ?? basename($rutaArchivo);

                    if (file_exists($rutaArchivo)) {
                        $zip->addFile($rutaArchivo, $nombreArchivo);
                    } else {
                        error_log("Archivo no encontrado: $rutaArchivo");
                    }
                }

                $zip->close();

                // Adjuntar el ZIP al correo
                $nombreLimpio = preg_replace('/[^a-zA-Z0-9 _-]/', '_', $nombreExportador);
                $nombreLimpio2 = preg_replace('/[^a-zA-Z0-9 _-]/', '_', $nombreLogistico);
                $nombreZip = "$suReferencia - $numero - $nombreLimpio.zip";
                $mail->addAttachment($zipFile, $nombreZip);
            } else {
                throw new Exception("No se pudo crear el archivo ZIP.");
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Envío de cuenta de gastos';

            $mail->Body = "
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <title>Envío de cuenta de gastos</title>
                                            <style>
                            body {
                            font-family: Arial, sans-serif;
                            background-color: #f4f4f4;
                            margin: 0;
                            padding: 40px 0;
                            display: flex;
                            justify-content: center;
                            }

                            .card {
                            background-color: white;
                            width: 90%;
                            max-width: 800px;
                            padding: 40px;
                            border-radius: 10px;
                            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                            }

                            .header {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            position: relative;
                            margin-bottom: 20px;
                            }

                            .header img {
                            position: absolute;
                            left: 0;
                            top: 0;
                            max-width: 80px;
                            }

                            .header-content {
                            text-align: center;
                            width: 100%;
                            }

                            .header-content p {
                            margin: 0;
                            }

                            .title{
                                margin-bottom: 3%;
                            }

                            hr {
                            margin: 20px 0;
                            }

                            .row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 10px;
                            }

                            .left, .right {
                            width: 48%;
                            }

                            .left {
                            text-align: left;
                            }

                            .right {
                            text-align: right;
                            }

                            .logistics {
                            margin-top: 20px;
                            }

                            .logistics-title {
                            font-weight: bold;
                            margin-bottom: 10px;
                            }

                            .logistics-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                            }

                            .logistics-table th,
                            .logistics-table td {
                            border: 1px solid #ccc;
                            padding: 8px;
                            text-align: center;
                            font-size: 14px;
                            }
                        </style>
                    </head>
                    <body>
                        <div style='text-align: left;'>
                            <img src='cid:logoimage' alt='Encabezado' style='max-width: 400px; margin-bottom: 20px;'>
                        </div>
                        <p>Estimado cliente,</p>
                        <p>Adjunto encontrará la cuenta de gastos correspondiente a su operación.</p>
                        <p>Para cualquier duda o aclaración, no dude en contactarnos.</p>
                        <br>
                        <div class='card'>
                            <div class='header'>
                                <img src='cid:logoAmex' alt='Logo'>
                                <div class='header-content'>
                                    <div class='title'>
                                        <p><strong>NOTIFICACIÓN</strong></p>
                                        <p><strong>CUENTA DE GASTOS</strong></p>
                                    </div>
                                    <div>
                                        <p><strong>Aduana:</strong> {$aduana}</p> 
                                        <p><strong>Referencia AMEX:</strong> {$numero}</p>
                                    </div>
                                </div>
                            </div>

                            <hr />

                            <div class='row'>
                                <div class='left'>
                                    <p><strong>FACTURADO A</strong></p>
                                    <p>{$nombreLimpio2}</p>
                                </div>
                                <div class='right'>
                                    <p><strong>COMPROBACIÓN DE GASTOS</strong></p>
                                    <p>Numero CG-000</p>
                                </div>
                            </div>

                            <div class='row'>
                                <div class='left'>
                                    <p><strong>EXPORTADOR:</strong></p>
                                    <p>{$nombreExportador}</p>
                                </div>
                                <div class='right'>
                                    <p><strong>FECHA:</strong></p>
                                    <p>{$fechaFormateada}</p>
                                </div>
                            </div>

                            <div class='logistics'>
                                <p class='logistics-title'>INFORMACIÓN LOGÍSTICA</p>
                                <table class='logistics-table' border='1' cellpadding='5' cellspacing='0'>
                                    <thead>
                                        <tr>
                                            <th>BOOKING</th>
                                            <th>BUQUE</th>
                                            <th>PTO. DESCARGA</th>
                                            <th>PTO. DESTINO</th>
                                            <th>SU REFERENCIA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{$booking}</td>
                                            <td>{$nombreBuque}</td>
                                            <td>{$puertoDescarga}</td>
                                            <td>{$puertoDestino}</td>
                                            <td>{$suReferencia}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr />
                            <p>Atentamente,</p>
                            <p>{$usuarioAltaRef}</p>
                        </div>
                    </body>
                    </html>
                    ";
            $mail->send();
 
            unlink($zipFile);

            $response['success'] = true;
            $response['message'] = 'Correo enviado correctamente.';
        } catch (Exception $mailError) {
            $response['success'] = false;
            $response['message'] = 'Error al enviar el correo en: ' . $mailError->getMessage();
        }
    } else {
        throw new Exception('Error al registrar pago.');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Limpia buffers antes de enviar JSON
if (ob_get_length()) {
    ob_end_clean();
}

echo json_encode($response);
exit;
?>