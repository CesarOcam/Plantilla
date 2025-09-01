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

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $referenciaId = $id;

    $mailsLogistico = $input['mails_logistico'] ?? [];
    $mailsAmex = $input['mails_amex'] ?? [];
    $response = ['success' => false, 'message' => '', 'debug' => ''];
    $usuarioAlta = $_SESSION['usuario_nombre'];

    if (!$id) {  // <-- usa $id, no $_POST
        echo json_encode(['success' => false, 'message' => 'ID de la referencia no proporcionado']);
        exit;
    }


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
                r.FechaAlta,
               u.name AS nombre_usuario_alta
            FROM conta_referencias r
            LEFT JOIN 2201aduanas a ON r.AduanaId = a.id2201aduanas
            LEFT JOIN 01clientes_exportadores exp ON r.ClienteExportadorId = exp.id01clientes_exportadores
            LEFT JOIN 01clientes_exportadores exp2 ON r.ClienteLogisticoId = exp2.id01clientes_exportadores
            LEFT JOIN transporte bq ON r.BuqueId = bq.idtransporte
            LEFT JOIN sec_users u ON r.UsuarioAlta = u.login
            WHERE r.Id = :id
        ");
    $stmt->bindParam(':id', $referenciaId, PDO::PARAM_INT);
    $stmt->execute();
    $referencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmtCg = $con->prepare("SELECT NumCg FROM conta_cuentas_kardex WHERE  Referencia = :id");
    $stmtCg->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtCg->execute();
    $Cg = $stmtCg->fetch(PDO::FETCH_ASSOC);
    $numCg = $Cg['NumCg'] ?? '';

    $fecha = $referencia['FechaAlta'];
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

        if (empty($mailsLogistico) && empty($mailsAmex)) {
            echo json_encode([
                'success' => false,
                'message' => 'No se seleccionaron correos para enviar'
            ]);
            exit;
        }

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
            
            // Construir asunto dinámico
            $asunto = "$suReferencia / $numCg / $numero / $nombreExportador";
            $mail->setFrom('notificaciones@grupoamexport.com', 'Notificaciones Amexport');
            $mail->AddEmbeddedImage('../../../img/LogoAmex.png', 'logoAmex');

            foreach ($mailsLogistico as $correo) {
                $mail->addAddress($correo);
            }
            foreach ($mailsAmex as $correo) {
                $mail->addCC($correo);
            }
            $mail->addCC('jesus.reyes@grupoamexport.com');
            //$mail->addCC('cesar.pulido@grupoamexport.com');

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
            $mail->Subject = $asunto;

            $mail->Body = '
                <html>
                <head>
                <meta charset="UTF-8">
                <title>Envío de cuenta de gastos</title>
                <style>
                a:link, a:visited, a:hover, a:active {
                    color: #000000 !important; /* color negro forzado */
                    text-decoration: none !important;
                }
                </style>
                </head>
                <body style="margin:0; padding:40px 0; background:#f4f4f4; font-family: Arial, sans-serif;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" style="max-width:800px; margin:auto; background:#fff; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); padding:40px;">
                    <tr>
                    <td style="position:relative; text-align:center; padding-bottom:20px;">
                        <img src="cid:logoAmex" alt="Logo" style="position:absolute; left:0; top:0; max-width:80px;">
                        <div style="margin:0 auto; width: 100%; max-width:600px;">
                        <div style="margin-bottom:12px;">
                            <p style="margin:0; font-weight:bold; font-size:18px;">NOTIFICACIÓN</p>
                            <p style="margin:0; font-weight:bold; font-size:18px;">CUENTA DE GASTOS</p>
                        </div>
                        <div>
                            <p style="margin:0;"><strong>Aduana:</strong> ' . $aduana . '</p>
                            <p style="margin:0;"><strong>Referencia AMEX:</strong> ' . $numero . '</p>
                        </div>
                        </div>
                    </td>
                    </tr>

                    <tr>
                    <td>
                        <hr style="border:none; border-top:1px solid #ddd; margin:20px 0;">
                    </td>
                    </tr>

                    <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
                        <tr>
                            <td width="48%" style="text-align:left; vertical-align:top;">
                            <p style="margin:0 0 6px 0; font-weight:bold;">FACTURADO A</p>
                            <p style="margin:0;">' . $nombreLimpio2 . '</p>
                            </td>
                            <td width="48%" style="text-align:right; vertical-align:top;">
                            <p style="margin:0 0 6px 0; font-weight:bold;">COMPROBACIÓN DE GASTOS</p>
                            <p style="margin:0;">Número ' . $Cg['NumCg'] . '</p>
                            </td>
                        </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
                        <tr>
                            <td width="48%" style="text-align:left; vertical-align:top;">
                            <p style="margin:0 0 6px 0; font-weight:bold;">EXPORTADOR:</p>
                            <p style="margin:0;">' . $nombreExportador . '</p>
                            </td>
                            <td width="48%" style="text-align:right; vertical-align:top;">
                            <p style="margin:0 0 6px 0; font-weight:bold;">FECHA:</p>
                            <p style="margin:0;">' . $fechaFormateada . '</p>
                            </td>
                        </tr>
                        </table>

                        <div style="margin-top:20px;">
                        <p style="font-weight:bold; margin-bottom:10px;">INFORMACIÓN LOGÍSTICA</p>
                        <table width="100%" cellpadding="5" cellspacing="0" border="1" style="border-collapse:collapse; font-size:14px; text-align:center; border-color:#ccc; margin-bottom:20px;">
                            <thead>
                            <tr style="background:#eee;">
                                <th>BOOKING</th>
                                <th>BUQUE</th>
                                <th>PTO. DESCARGA</th>
                                <th>PTO. DESTINO</th>
                                <th>SU REFERENCIA</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>' . $booking . '</td>
                                <td>' . $nombreBuque . '</td>
                                <td>' . $puertoDescarga . '</td>
                                <td>' . $puertoDestino . '</td>
                                <td>' . $suReferencia . '</td>
                            </tr>
                            </tbody>
                        </table>
                        </div>

                        <hr style="border:none; border-top:1px solid #ddd; margin:20px 0;">

                        <p style="margin:0 0 5px 0;">Atentamente,</p>
                        <p style="margin:0;">' . $usuarioAlta . '</p>
                    </td>
                    </tr>
                </table>
                </body>
                </html>
                ';
            $mail->send();

            unlink($zipFile);
            $response['success'] = true;
            $response['message'] = 'Correo enviado correctamente.';
        } catch (Exception $mailError) {
            $output = ob_get_clean(); // Captura cualquier salida previa
            $response['success'] = false;
            $response['message'] = 'Error al enviar el correo: ' . $mailError->getMessage();
            $response['debug'] = $output; // Esto mostrará warnings/notices antes del JSON
        } catch (Throwable $e) { // Captura cualquier otro error
            $output = ob_get_clean();
            $response['success'] = false;
            $response['message'] = 'Error general: ' . $e->getMessage();
            $response['debug'] = $output;
        }
    } else {
        throw new Exception('Error al registrar pago.');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Si quedó algún buffer no capturado
if (ob_get_length()) {
    $response['debug'] .= ob_get_clean();
}
echo json_encode($response);
exit;
?>