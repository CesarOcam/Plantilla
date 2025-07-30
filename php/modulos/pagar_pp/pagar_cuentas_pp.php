<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../conexion.php');
// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = $_POST['ids'] ?? '';
    $total = $_POST['total'] ?? 0;
    $fecha = $_POST['fecha'] ?? '';
    $beneficiario = $_POST['beneficiario'] ?? '';
    $subcuenta = $_POST['subcuenta'] ?? '';

    $empresa = 2;
    $usuarioAlta = $_SESSION['usuario_id'];
    $numero_poliza = '';
    $prefijo = 'D'; // Siempre prefijo D
    $concepto = 'PAGO DE CUENTA';
    $activo = 1;

    $sql_ultimo = "SELECT Numero FROM conta_polizas WHERE LEFT(Numero, 1) = ? ORDER BY CAST(SUBSTRING(Numero, 2) AS UNSIGNED) DESC LIMIT 1";
    $stmt_ultimo = $con->prepare($sql_ultimo);
    $stmt_ultimo->execute([$prefijo]);

    $ultimo_numero = 0;
    if ($fila = $stmt_ultimo->fetch(PDO::FETCH_ASSOC)) {
        $ultimo_numero = (int) substr($fila['Numero'], 1); // Extraer número sin prefijo
    }

    $nuevo_numero = $ultimo_numero + 1;
    $numero_poliza = $prefijo . str_pad($nuevo_numero, 7, '0', STR_PAD_LEFT);

    //---------------------------------------GENERAR POLIZA--------------------------------------------------------
    $sql_insert_poliza = "INSERT INTO conta_polizas 
        (
            BeneficiarioId, EmpresaId, Numero, Importe, Concepto, Fecha, Activo, FechaAlta, UsuarioAlta
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $beneficiario,
        $empresa,
        $numero_poliza,
        $total,
        $concepto,
        $fecha,
        $activo,
        $fecha,
        $usuarioAlta
    ];
    $stmt_poliza = $con->prepare($sql_insert_poliza);
    $resultado = $stmt_poliza->execute($params);

    if (!$resultado) {
        die("Error al guardar la póliza: " . implode(", ", $stmt_poliza->errorInfo()));
    }

    $poliza_id = $con->lastInsertId();
    //-------------------------------------GENERAR PARTIDAS----------------------------------------------------------

    try {
        $con->beginTransaction();
        $id_array = array_filter(explode(',', $ids)); // Quitamos vacíos
        $cargo = 0;
        $observacion = 'Pago de cuenta';
        $enKardex = 0;
        $Pagada = 1;

        $sql_datos_partida = "SELECT PolizaId, SubcuentaId, ReferenciaId, Cargo, NumeroFactura, UsuarioSolicitud, created_by FROM conta_partidaspolizas WHERE Partida = ?";
        $stmt_datos = $con->prepare($sql_datos_partida);

        $sql_insert_partida = "INSERT INTO conta_partidaspolizas 
            (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Pagada, Observaciones, Activo, EnKardex, CuentaCont, NumeroFactura, UsuarioSolicitud, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        $stmt_insert = $con->prepare($sql_insert_partida);

        $sql_update_pagada_original = "UPDATE conta_partidaspolizas SET Pagada = 1 WHERE Partida = ?";
        $stmt_update_original = $con->prepare($sql_update_pagada_original);

        foreach ($id_array as $id_partida) {
            $stmt_datos->execute([$id_partida]);
            $datos = $stmt_datos->fetch(PDO::FETCH_ASSOC);

            if ($datos) {
                $subcuentaId = $datos['SubcuentaId'];
                $referenciaId = $datos['ReferenciaId'];
                $cargoAbonado = $datos['Cargo'];
                $polizaOriginalId = $datos['PolizaId'];
                $NumFactura = $datos['NumeroFactura'];
                $solicitadoPor = $datos['UsuarioSolicitud'];
                $aprobadoPor = $datos['created_by'];

                // Insertamos la nueva partida relacionada a la nueva póliza
                $ok = $stmt_insert->execute([
                    $poliza_id,
                    $subcuentaId,
                    $referenciaId,
                    $cargo,
                    $cargoAbonado,
                    $Pagada,
                    $observacion,
                    $activo,
                    $enKardex,
                    $NumFactura,
                    $solicitadoPor,
                    $aprobadoPor
                ]);

                if (!$ok) {
                    $error = $stmt_insert->errorInfo();
                    throw new Exception("Error al insertar partida: " . $error[2]);
                }

                // Actualizamos la póliza original a pagada
                $okOriginal = $stmt_update_original->execute([$id_partida]);

                if (!$okOriginal) {
                    $error = $stmt_update_original->errorInfo();
                    throw new Exception("Error al actualizar póliza original (ID: $polizaOriginalId): " . $error[2]);
                }

            } else {
                throw new Exception("No se encontró partida con ID: $id_partida");
            }
        }

        // === Insertar la partida de salida bancaria ===
        $sql_insert_banco = "INSERT INTO conta_partidaspolizas 
            (PolizaId, SubcuentaId, ReferenciaId, Cargo, Abono, Pagada, Observaciones, Activo, EnKardex, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_banco = $con->prepare($sql_insert_banco);

        $referenciaBanco = null;
        $cargoBanco = $total;
        $abonoBanco = 0;
        $observacionBanco = 'Salida de banco por pago de cuentas';

        $okBanco = $stmt_banco->execute([
            $poliza_id,
            $subcuenta,
            $referenciaBanco,
            $cargoBanco,
            $abonoBanco,
            $Pagada,
            $observacionBanco,
            $activo,
            $enKardex,
            $usuarioAlta
        ]);

        if (!$okBanco) {
            $error = $stmt_banco->errorInfo();
            throw new Exception("Error al insertar partida bancaria: " . $error[2]);
        }

        // === Marcar la nueva póliza como pagada ===
        $sql_update_poliza = "UPDATE conta_polizas SET Pagada = 1 WHERE Id = ?";
        $stmt_update = $con->prepare($sql_update_poliza);
        $okUpdate = $stmt_update->execute([$poliza_id]);

        if (!$okUpdate) {
            $error = $stmt_update->errorInfo();
            throw new Exception("Error al actualizar estado de póliza nueva: " . $error[2]);
        }

        $con->commit();

        /////////////////////////////////////////////////////// ENVÍO DE CORREO ///////////////////////////////////////////////////////////////////////

        require '../../../lib/mailer/vendor/autoload.php';
        require '../../vistas/pdfs/cuenta_gastos_mail.php';

        $stmt = $con->prepare("
            SELECT 
                r.AduanaId,
                a.nombre_corto_aduana AS nombre_aduana,
                r.Numero,
                r.ClienteExportadorId,
                exp.razonSocial_exportador AS nombre_exportador,
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
                //$mail->addAddress('jesus.reyes@grupoamexport.com');
                $mail->addAddress('cesar.pulido@grupoamexport.com');
                $mail->AddEmbeddedImage('Amexport.jpeg', 'logoimage');
                $mail->AddEmbeddedImage('LogoAmex.png', 'logoAmex');
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
                                    <p>{$nombreBeneficiario}</p>
                                </div>
                                <div class='right'>
                                    <p><strong>COMPROBACIÓN DE GASTOS</strong></p>
                                    <p>Numero CG-NUM</p>
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
                $response['mensaje'] = 'Pago registrado y correo enviado correctamente.';
            } catch (Exception $mailError) {
                // El mail falló, pero la lógica base fue exitosa
                $response['success'] = false;
                $response['mensaje'] = 'Pago registrado, pero error al enviar correo: ' . $mailError->getMessage();
            }
        } else {
            throw new Exception('Error al registrar pago.');
        }
        /////////////////////////////////////////////////// FIN DEL CORREO ////////////////////////////////////////////////////
        // Datos adicionales para JS
        $response['datos'] = [
            'ids' => $id_array,
            'total' => $total,
            'fecha' => $fecha,
            'beneficiario' => $beneficiario,
            'subcuenta' => $subcuenta,
            'poliza' => $numero_poliza
        ];

        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        if (isset($con)) {
            $con->rollBack();
        }
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al guardar partidas: ' . $e->getMessage()
        ]);
        exit;
    }
}