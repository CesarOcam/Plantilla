<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../lib/mailer/vendor/autoload.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Solo continúa si se recibió una solicitud POST válida
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);

    if (!isset($data['accion']) || $data['accion'] !== 'enviar_correo') {
        throw new Exception('Acción inválida.');
    }

    // Aquí puedes ejecutar tu lógica de SQL u otra cosa que defina $resultado
    // Por ahora, para pruebas:
    $resultado = true;

    if ($resultado) {
        $mail = new PHPMailer(true);

        try {

            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.mail.us-east-1.awsapps.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'notificaciones@grupoamexport.com';
            $mail->Password = 'AMEXPORT.2024';  // Usa tu contraseña de app
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            /*$mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cesar.amexport@gmail.com';
            $mail->Password   = 'igcc eamd dbak fpti';  // Usa tu contraseña de app
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;*/

            // Destinatarios
            //$mail->setFrom('cesar.amexport@gmail.com', 'Amexport Operaciones');
            $mail->setFrom('notificaciones@grupoamexport.com', 'Notificaciones Amexport');
            $mail->addAddress('cesar.pulido@grupoamexport.com');

            // Imagen
            $mail->AddEmbeddedImage('Amexport.jpeg', 'logoimage');
            $mail->AddEmbeddedImage('LogoAmex.png', 'logoAmex');

            // Contenido HTML
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8'; // Esta línea soluciona los problemas de acentos
            $mail->Subject = 'Envío de cuenta de gastos';

            $mail->Body = '
            <html>
            <head>
                <meta charset="UTF-8">
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
                    <div style="text-align: left;">
                        <img src="cid:logoimage" alt="Encabezado" max-width: 400px; margin-bottom: 20px;">
                    </div>
                    <p>Estimado cliente,</p>
                    <p>Adjunto encontrará la cuenta de gastos correspondiente a su operación.</p>
                    <p>Para cualquier duda o aclaración, no dude en contactarnos.</p>
                    <br>
                    <div class="card">
                        <div class="header">
                        <img src="cid:logoAmex" alt="Logo">
                        <div class="header-content">
                            <div class="title">
                                <p><strong>NOTIFICACIÓN</strong></p>
                                <p><strong>CUENTA DE GASTOS</strong></p>
                            </div>
                            <div>
                            <p><strong>Aduana:</strong>NombreAduana</p> 
                            <p><strong>Referencia AMEX:</strong> Num.Referencia</p>
                            </div>

                        </div>
                        </div>

                        <hr />

                        <div class="row">
                        <div class="left">
                            <p><strong>FACTURADO A</strong></p>
                            <p>NOMBRE EJ. IFS NEUTRAL MARITIME</p>
                        </div>
                        <div class="right">
                            <p><strong>COMPROBACIÓN DE GASTOS</strong></p>
                            <p>CG-NUM</p>
                        </div>
                        </div>

                        <div class="row">
                        <div class="left">
                            <p><strong>EXPORTADOR:</strong></p>
                            <p>NOMBRE EXPORTADOR</p>
                        </div>
                        <div class="right">
                            <p><strong>FECHA:</strong> DD/MM/YY</p>
                        </div>
                        </div>

                        <div class="logistics">
                        <p class="logistics-title">INFORMACIÓN LOGÍSTICA</p>
                        <table class="logistics-table">
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
                                <td>MEXF16928500</td>
                                <td>LONCOMILLA</td>
                                <td>SANTOS, BRASIL</td>
                                <td>SANTOS, BRASIL</td>
                                <td>MEXF16928500</td>
                            </tr>
                            </tbody>
                        </table>
                        </div>

                        <hr />

                        <p>Atentamente,</p>
                        <p>Yamileth Montiel</p>
                    </div>
                </body>
            </html>
        ';

            $mail->send();

        } catch (Exception $mailError) {
            // Error en el correo, lo capturamos y lo lanzamos como parte del flujo principal
            throw new Exception("Error al enviar el correo: " . $mailError->getMessage());
        }
        $response['success'] = true;
        $response['message'] = 'Correo enviado correctamente.';
    } else {
        throw new Exception('No se pudo ejecutar la lógica previa al envío del correo.');
    }

} catch (Exception $e) {
    $response['message'] = 'Error al enviar el correo: ' . $e->getMessage();
}

echo json_encode($response);
