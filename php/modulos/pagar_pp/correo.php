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

        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cesar.amexport@gmail.com';
        $mail->Password   = 'igcc eamd dbak fpti';  // Usa tu contraseña de app
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Destinatarios
        $mail->setFrom('cesar.amexport@gmail.com', 'Amexport Operaciones');
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
            </head>
                <body style="font-family: Arial, sans-serif; color: #333;">
                    <div style="text-align: left;">
                        <img src="cid:logoimage" alt="Encabezado" max-width: 400px; margin-bottom: 20px;">
                    </div>
                    <p>Estimado cliente,</p>
                    <p>Adjunto encontrará la cuenta de gastos correspondiente a su operación.</p>
                    <p>Para cualquier duda o aclaración, no dude en contactarnos.</p>
                    <br>
                    <p>Saludos cordiales,<br><strong>Amexport Operaciones</strong></p>
                </body>
            </html>
        ';

        $mail->send();
        $response['success'] = true;
        $response['message'] = 'Correo enviado correctamente.';
    } else {
        throw new Exception('No se pudo ejecutar la lógica previa al envío del correo.');
    }

} catch (Exception $e) {
    $response['message'] = 'Error al enviar el correo: ' . $e->getMessage();
}

echo json_encode($response);
