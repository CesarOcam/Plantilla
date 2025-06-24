<?php
session_start();
include('../conexion.php');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
    exit;
}

// Lista de campos obligatorios que quieres validar (modifica según tus requerimientos)
$camposRequeridos = ['nombre', 'rfc', 'tipo', 'persona'];

$faltantes = [];
foreach ($camposRequeridos as $campo) {
    if (empty($_POST[$campo])) {
        $faltantes[] = $campo;
    }
}

if (!empty($faltantes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Por favor llene los campos requeridos: ' . implode(', ', $faltantes)
    ]);
    exit;
}

// Ahora, ya sabes que los campos requeridos están presentes, continua con el resto del código

// Recoger todos los valores
$nombre = trim($_POST['nombre']);
$curp = trim($_POST['curp'] ?? '');
$rfc = trim($_POST['rfc']);
$tipo_persona = $_POST['persona'];
$tipo_cliente = $_POST['tipo'];
$nombre_conocido = !empty(trim($_POST['nombre_corto'] ?? '')) ? trim($_POST['nombre_corto']) : null;
$contacto = !empty(trim($_POST['contacto_cliente'] ?? '')) ? trim($_POST['contacto_cliente']) : null;
$tel = !empty(trim($_POST['telefono_cliente'] ?? '')) ? trim($_POST['telefono_cliente']) : null;

$calle = !empty(trim($_POST['calle'] ?? '')) ? trim($_POST['calle']) : null;
$num_exterior = !empty($_POST['num_exterior']) ? $_POST['num_exterior'] : null;
$num_interior = !empty($_POST['num_interior']) ? $_POST['num_interior'] : null;
$cp = !empty($_POST['cp']) ? $_POST['cp'] : null;
$colonia = !empty(trim($_POST['colonia'] ?? '')) ? trim($_POST['colonia']) : null;
$localidad = !empty(trim($_POST['localidad'] ?? '')) ? trim($_POST['localidad']) : null;

$municipio = !empty(trim($_POST['municipio'] ?? '')) ? trim($_POST['municipio']) : null;
$pais = !empty($_POST['pais']) ? $_POST['pais'] : null;
$estado = !empty($_POST['estado']) ? $_POST['estado'] : null;
$quien_paga = isset($_POST['pagaCon_cliente']) && $_POST['pagaCon_cliente'] !== '' ? (int) $_POST['pagaCon_cliente'] : null;
$logistico = !empty($_POST['logistico_asociado']) ? $_POST['logistico_asociado'] : null;
$email_trafico = !empty(trim($_POST['emails_trafico'] ?? '')) ? trim($_POST['emails_trafico']) : null;
$status = isset($_POST['status_exportador']) && $_POST['status_exportador'] !== '' ? (int) $_POST['status_exportador'] : null;

function obtenerFechaHoraActual()
{
    return date("Y-m-d H:i:s");
}

$fecha_alta = obtenerFechaHoraActual();
$activo = 1;
$usuarioAlta = $_SESSION['usuario_id'];

$sql = "INSERT INTO 01clientes_exportadores 
(
    razonSocial_exportador, curp_exportador, rfc_exportador, tipoClienteExportador, tipo_cliente,
    nombreCorto_exportador, calle_exportador, noExt_exportador, noInt_exportador, codigoPostal_exportador,
    pagaCon_cliente, colonia_exportador, localidad_exportador, municipio_exportador,
    idcat11_estado, id2204clave_pais, contacto_cliente, telefono_cliente, emails_trafico, logistico_asociado,
    status_exportador, fechaAlta_exportador, usuarioAlta_exportador
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params = [
    $nombre,
    $curp,
    $rfc,
    $tipo_persona,
    $tipo_cliente,
    $nombre_conocido,
    $calle,
    $num_exterior,
    $num_interior,
    $cp,
    $quien_paga,
    $colonia,
    $localidad,
    $municipio,
    $estado,
    $pais,
    $contacto,
    $tel,
    $email_trafico,
    $logistico,
    $status,
    $fecha_alta,
    $usuarioAlta
];

if (count($params) !== substr_count($sql, '?')) {
    echo json_encode([
        'success' => false,
        'message' => "Error: El número de parámetros no coincide con el número de tokens `?` en la consulta."
    ]);
} else {
    $stmt = $con->prepare($sql);
    if ($stmt) {
        try {
            $resultado = $stmt->execute($params);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => "Cliente guardado correctamente."
                ]);
            }
        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();

            if (strpos($errorMessage, "Data too long for column 'rfc_exportador'") !== false) {
                echo json_encode([
                    'success' => false,
                    'message' => "El RFC excede el número de caracteres permitido."
                ]);
            } elseif ($e->getCode() == 23000) {
                echo json_encode([
                    'success' => false,
                    'message' => "Los datos ingresados ya existen en la base de datos."
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Error al guardar: " . $errorMessage
                ]);
            }
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Error al preparar la consulta: " . implode(", ", $con->errorInfo())
        ]);
    }
}
?>
